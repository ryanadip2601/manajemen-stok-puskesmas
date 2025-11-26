# Desain Database - Manajemen Stok Barang UPTD Puskesmas Karang Rejo

## Diagram Relasi Database (ERD)

```
┌─────────────────┐
│     USERS       │
├─────────────────┤
│ id (PK)         │
│ name            │
│ email (unique)  │
│ password        │
│ role            │
│ created_at      │
│ updated_at      │
└────────┬────────┘
         │
         │ (1:N)
         │
         ├──────────────────────────┬──────────────────────────┐
         │                          │                          │
         ▼                          ▼                          ▼
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│   STOCK_IN      │       │   STOCK_OUT     │       │      LOGS       │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ id (PK)         │       │ id (PK)         │       │ id (PK)         │
│ item_id (FK)    │       │ item_id (FK)    │       │ user_id (FK)    │
│ user_id (FK)    │       │ user_id (FK)    │       │ action          │
│ quantity        │       │ quantity        │       │ description     │
│ date            │       │ date            │       │ created_at      │
│ notes           │       │ notes           │       └─────────────────┘
│ created_at      │       │ created_at      │
│ updated_at      │       │ updated_at      │
└────────┬────────┘       └────────┬────────┘
         │                         │
         │ (N:1)                   │ (N:1)
         │                         │
         └────────┬────────────────┘
                  │
                  ▼
         ┌─────────────────┐
         │     ITEMS       │
         ├─────────────────┤
         │ id (PK)         │
         │ category_id(FK) │
         │ unit_id (FK)    │
         │ code (unique)   │
         │ name            │
         │ description     │
         │ stock           │
         │ minimum_stock   │
         │ created_at      │
         │ updated_at      │
         └────────┬────────┘
                  │
                  │ (N:1)
         ┌────────┴────────┐
         │                 │
         ▼                 ▼
┌─────────────────┐  ┌─────────────────┐
│   CATEGORIES    │  │     UNITS       │
├─────────────────┤  ├─────────────────┤
│ id (PK)         │  │ id (PK)         │
│ name (unique)   │  │ name (unique)   │
│ description     │  │ symbol          │
│ created_at      │  │ created_at      │
│ updated_at      │  │ updated_at      │
└─────────────────┘  └─────────────────┘
```

## Detail Tabel Database

### 1. Table: users
| Column     | Type         | Length | Attributes                    | Description              |
|------------|--------------|--------|-------------------------------|--------------------------|
| id         | BIGINT       | -      | PK, AUTO_INCREMENT, UNSIGNED  | Primary Key              |
| name       | VARCHAR      | 255    | NOT NULL                      | Nama pengguna            |
| email      | VARCHAR      | 255    | NOT NULL, UNIQUE              | Email (username login)   |
| password   | VARCHAR      | 255    | NOT NULL                      | Password terenkripsi     |
| role       | ENUM         | -      | 'admin', 'staff'              | Role pengguna            |
| created_at | TIMESTAMP    | -      | NULL                          | Waktu pembuatan          |
| updated_at | TIMESTAMP    | -      | NULL                          | Waktu update terakhir    |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX (email)

---

### 2. Table: categories
| Column      | Type         | Length | Attributes                    | Description              |
|-------------|--------------|--------|-------------------------------|--------------------------|
| id          | BIGINT       | -      | PK, AUTO_INCREMENT, UNSIGNED  | Primary Key              |
| name        | VARCHAR      | 100    | NOT NULL, UNIQUE              | Nama kategori            |
| description | TEXT         | -      | NULL                          | Deskripsi kategori       |
| created_at  | TIMESTAMP    | -      | NULL                          | Waktu pembuatan          |
| updated_at  | TIMESTAMP    | -      | NULL                          | Waktu update terakhir    |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX (name)

---

### 3. Table: units
| Column     | Type         | Length | Attributes                    | Description              |
|------------|--------------|--------|-------------------------------|--------------------------|
| id         | BIGINT       | -      | PK, AUTO_INCREMENT, UNSIGNED  | Primary Key              |
| name       | VARCHAR      | 50     | NOT NULL, UNIQUE              | Nama satuan (Box, Pcs)   |
| symbol     | VARCHAR      | 20     | NOT NULL                      | Simbol satuan            |
| created_at | TIMESTAMP    | -      | NULL                          | Waktu pembuatan          |
| updated_at | TIMESTAMP    | -      | NULL                          | Waktu update terakhir    |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX (name)

---

### 4. Table: items
| Column        | Type         | Length | Attributes                    | Description                |
|---------------|--------------|--------|-------------------------------|----------------------------|
| id            | BIGINT       | -      | PK, AUTO_INCREMENT, UNSIGNED  | Primary Key                |
| category_id   | BIGINT       | -      | FK, UNSIGNED, NOT NULL        | Foreign Key ke categories  |
| unit_id       | BIGINT       | -      | FK, UNSIGNED, NOT NULL        | Foreign Key ke units       |
| code          | VARCHAR      | 50     | NOT NULL, UNIQUE              | Kode barang                |
| name          | VARCHAR      | 255    | NOT NULL                      | Nama barang                |
| description   | TEXT         | -      | NULL                          | Deskripsi barang           |
| stock         | INT          | -      | NOT NULL, DEFAULT 0           | Jumlah stok saat ini       |
| minimum_stock | INT          | -      | NOT NULL, DEFAULT 0           | Stok minimum (alert)       |
| created_at    | TIMESTAMP    | -      | NULL                          | Waktu pembuatan            |
| updated_at    | TIMESTAMP    | -      | NULL                          | Waktu update terakhir      |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE INDEX (code)
- FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
- FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE RESTRICT
- INDEX (category_id)
- INDEX (unit_id)

---

### 5. Table: stock_in
| Column     | Type         | Length | Attributes                    | Description                |
|------------|--------------|--------|-------------------------------|----------------------------|
| id         | BIGINT       | -      | PK, AUTO_INCREMENT, UNSIGNED  | Primary Key                |
| item_id    | BIGINT       | -      | FK, UNSIGNED, NOT NULL        | Foreign Key ke items       |
| user_id    | BIGINT       | -      | FK, UNSIGNED, NOT NULL        | User yang input            |
| quantity   | INT          | -      | NOT NULL                      | Jumlah barang masuk        |
| date       | DATE         | -      | NOT NULL                      | Tanggal barang masuk       |
| notes      | TEXT         | -      | NULL                          | Catatan tambahan           |
| created_at | TIMESTAMP    | -      | NULL                          | Waktu pembuatan            |
| updated_at | TIMESTAMP    | -      | NULL                          | Waktu update terakhir      |

**Indexes:**
- PRIMARY KEY (id)
- FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
- INDEX (item_id)
- INDEX (user_id)
- INDEX (date)

---

### 6. Table: stock_out
| Column     | Type         | Length | Attributes                    | Description                |
|------------|--------------|--------|-------------------------------|----------------------------|
| id         | BIGINT       | -      | PK, AUTO_INCREMENT, UNSIGNED  | Primary Key                |
| item_id    | BIGINT       | -      | FK, UNSIGNED, NOT NULL        | Foreign Key ke items       |
| user_id    | BIGINT       | -      | FK, UNSIGNED, NOT NULL        | User yang input            |
| quantity   | INT          | -      | NOT NULL                      | Jumlah barang keluar       |
| date       | DATE         | -      | NOT NULL                      | Tanggal barang keluar      |
| notes      | TEXT         | -      | NULL                          | Catatan tambahan           |
| created_at | TIMESTAMP    | -      | NULL                          | Waktu pembuatan            |
| updated_at | TIMESTAMP    | -      | NULL                          | Waktu update terakhir      |

**Indexes:**
- PRIMARY KEY (id)
- FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
- INDEX (item_id)
- INDEX (user_id)
- INDEX (date)

---

### 7. Table: logs
| Column      | Type         | Length | Attributes                    | Description                |
|-------------|--------------|--------|-------------------------------|----------------------------|
| id          | BIGINT       | -      | PK, AUTO_INCREMENT, UNSIGNED  | Primary Key                |
| user_id     | BIGINT       | -      | FK, UNSIGNED, NULL            | User yang melakukan aksi   |
| action      | VARCHAR      | 100    | NOT NULL                      | Jenis aksi (create, update)|
| description | TEXT         | -      | NULL                          | Detail log                 |
| created_at  | TIMESTAMP    | -      | NULL                          | Waktu pembuatan            |

**Indexes:**
- PRIMARY KEY (id)
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
- INDEX (user_id)
- INDEX (created_at)

---

## Relasi Antar Tabel

1. **users → stock_in** (One to Many)
   - Satu user dapat melakukan banyak transaksi barang masuk
   
2. **users → stock_out** (One to Many)
   - Satu user dapat melakukan banyak transaksi barang keluar

3. **users → logs** (One to Many)
   - Satu user dapat memiliki banyak log aktivitas

4. **categories → items** (One to Many)
   - Satu kategori dapat memiliki banyak item barang

5. **units → items** (One to Many)
   - Satu satuan dapat digunakan oleh banyak item barang

6. **items → stock_in** (One to Many)
   - Satu item dapat memiliki banyak riwayat barang masuk

7. **items → stock_out** (One to Many)
   - Satu item dapat memiliki banyak riwayat barang keluar

---

## Logika Bisnis

### Stok Otomatis
1. **Barang Masuk (stock_in):**
   - Ketika record baru dibuat → `items.stock += stock_in.quantity`
   - Ketika record diupdate → `items.stock = items.stock - old_quantity + new_quantity`
   - Ketika record dihapus → `items.stock -= stock_in.quantity`

2. **Barang Keluar (stock_out):**
   - Ketika record baru dibuat → `items.stock -= stock_out.quantity`
   - Validasi: `items.stock` tidak boleh < 0 (stock tidak bisa minus)
   - Ketika record diupdate → `items.stock = items.stock + old_quantity - new_quantity`
   - Ketika record dihapus → `items.stock += stock_out.quantity`

3. **Alert Stok Minimum:**
   - Tampilkan notifikasi jika `items.stock <= items.minimum_stock`
