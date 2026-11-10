"""Convert a verified SIMBAR SQLite snapshot to PostgreSQL INSERT statements."""

import argparse
import math
from pathlib import Path
import re
import sqlite3
import sys


TABLES = (
    "categories", "kategori_aset", "ruangan", "units", "users", "barang_aset",
    "items", "login_histories", "logs", "sbbk_documents", "pemeliharaan_aset",
    "penyusutan_aset", "permintaan_barang", "permintaan_restok", "stock_in",
    "stock_out", "stock_out_requests", "usulan_penerimaan_barang",
    "usulan_permintaan_barang",
)
CREDENTIAL_FIELDS = {"password", "reset_code", "reset_code_expires_at", "remember_token"}
BATCH_SIZE = 100


def identifier(name):
    if not re.fullmatch(r"[a-z_][a-z_0-9]*", name):
        raise ValueError("An unexpected column name was found in the SQLite schema.")
    return f'"{name}"'


def literal(value, source_type):
    if value is None:
        return "NULL"
    if source_type.lower() == "tinyint(1)":
        if value not in (0, 1):
            raise ValueError("The backup contains an unexpected boolean value.")
        return "TRUE" if value else "FALSE"
    if isinstance(value, int) or (isinstance(value, float) and math.isfinite(value)):
        return str(value)
    if isinstance(value, str) and "\x00" not in value:
        return "'" + value.replace("'", "''") + "'"
    raise ValueError("The backup contains a value PostgreSQL cannot import.")


def inactive_rooms(db):
    violations = list(db.execute("PRAGMA foreign_key_check"))
    if any(table != "barang_aset" or parent != "ruangan" for table, _, parent, _ in violations):
        raise ValueError("The backup has an unresolved foreign key outside of barang_aset.ruangan_id.")
    return [
        row[0] for row in db.execute(
            "SELECT DISTINCT a.ruangan_id FROM barang_aset a "
            "LEFT JOIN ruangan r ON r.id = a.ruangan_id WHERE r.id IS NULL ORDER BY a.ruangan_id"
        )
    ]


def write_table(db, table, output):
    sql_table = identifier(table)
    columns = [
        (identifier(name), source_type)
        for _, name, source_type, *_ in db.execute(f"PRAGMA table_info({sql_table})")
        if table != "users" or name not in CREDENTIAL_FIELDS
    ]
    column_names = ", ".join(name for name, _ in columns)
    rows = db.execute(f'SELECT {column_names} FROM {sql_table} ORDER BY "id"')
    while batch := rows.fetchmany(BATCH_SIZE):
        values = [
            "(" + ", ".join(
                literal(value, column[1]) for value, column in zip(row, columns, strict=True)
            ) + ")"
            for row in batch
        ]
        print(f"INSERT INTO simbar.{sql_table} ({column_names}) VALUES\n", end="", file=output)
        print(",\n".join(values) + ";", file=output)


def export(db, output):
    if db.execute("PRAGMA integrity_check").fetchall() != [("ok",)]:
        raise ValueError("SQLite integrity check failed.")
    rooms = inactive_rooms(db)
    sequences = dict(db.execute("SELECT name, seq FROM sqlite_sequence"))
    counts = {table: db.execute(f'SELECT count(*) FROM "{table}"').fetchone()[0] for table in TABLES}
    counts["ruangan"] += len(rooms)

    print("BEGIN;\nSET LOCAL standard_conforming_strings = on;", file=output)
    targets = ", ".join(f"simbar.{identifier(table)}" for table in TABLES)
    print(f"LOCK TABLE {targets} IN SHARE ROW EXCLUSIVE MODE;", file=output)
    print("DO $check_empty$ BEGIN", file=output)
    for table in TABLES:
        print(
            f"    IF EXISTS (SELECT 1 FROM simbar.{identifier(table)}) THEN "
            f"RAISE EXCEPTION 'simbar.{table} already contains data'; END IF;",
            file=output,
        )
    print("END $check_empty$;", file=output)

    for table in TABLES:
        write_table(db, table, output)
        if table == "ruangan":
            for room_id in rooms:
                if not isinstance(room_id, int):
                    raise ValueError("An invalid missing room ID was found.")
                print(
                    "INSERT INTO simbar.ruangan (id, nama_ruangan, is_active, is_migration_placeholder) "
                    f"VALUES ({room_id}, 'Ruangan lama #{room_id}', FALSE, TRUE);",
                    file=output,
                )
        sequence = sequences.get(table, 0)
        if not isinstance(sequence, int) or sequence < 0:
            raise ValueError("An invalid SQLite sequence value was found.")
        print(
            f"SELECT setval(pg_get_serial_sequence('simbar.{table}', 'id'), "
            f"GREATEST(COALESCE(MAX(id), 0), {sequence}, 1), "
            f"GREATEST(COALESCE(MAX(id), 0), {sequence}) > 0) "
            f"FROM simbar.{identifier(table)};",
            file=output,
        )

    print("DO $check_counts$ BEGIN", file=output)
    for table, count in counts.items():
        print(
            f"    IF (SELECT count(*) FROM simbar.{identifier(table)}) <> {count} THEN "
            f"RAISE EXCEPTION 'Unexpected row count for simbar.{table}'; END IF;",
            file=output,
        )
    print("END $check_counts$;\nCOMMIT;", file=output)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("backup", type=Path, help="A standalone SQLite snapshot")
    args = parser.parse_args()
    source = args.backup.resolve(strict=True)
    if any(source.with_name(source.name + suffix).exists() for suffix in ("-wal", "-shm", "-journal")):
        raise ValueError("Export requires a standalone SQLite snapshot without journal files.")
    sys.stdout.reconfigure(encoding="utf-8", newline="\n")
    with sqlite3.connect(source.as_uri() + "?mode=ro&immutable=1", uri=True) as db:
        export(db, sys.stdout)


if __name__ == "__main__":
    try:
        main()
    except (OSError, sqlite3.Error, ValueError) as error:
        sys.exit(f"SIMBAR export failed: {error}")
