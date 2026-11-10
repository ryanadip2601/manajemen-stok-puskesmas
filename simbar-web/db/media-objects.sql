CREATE TABLE IF NOT EXISTS simbar.media_objects (
    legacy_path text PRIMARY KEY,
    legacy_reference text UNIQUE,
    cloudinary_asset_id text NOT NULL UNIQUE,
    cloudinary_public_id text NOT NULL UNIQUE,
    cloudinary_version bigint NOT NULL CHECK (cloudinary_version > 0),
    format text NOT NULL CHECK (format IN ('jpg', 'png')),
    file_size integer NOT NULL CHECK (file_size > 0),
    sha256 text NOT NULL CHECK (sha256 ~ '^[a-f0-9]{64}$')
);

ALTER TABLE simbar.media_objects ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE simbar.media_objects FROM PUBLIC, anon, authenticated, service_role;
