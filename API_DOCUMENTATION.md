# REST API Documentation - Manajemen Stok Barang

**Base URL:** `http://localhost:8000/api`

**Content-Type:** `application/json`

**Authentication:** Bearer Token (Laravel Sanctum)

---

## 📌 Authentication

### 1. Login
**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
  "email": "admin@puskesmas.com",
  "password": "password123"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin Puskesmas",
      "email": "admin@puskesmas.com",
      "role": "admin",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
    "token_type": "Bearer"
  }
}
```

**Response Error (401):**
```json
{
  "success": false,
  "message": "Email atau password salah"
}
```

---

### 2. Logout
**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Logout berhasil"
}
```

---

### 3. Get Current User
**Endpoint:** `GET /api/me`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Admin Puskesmas",
    "email": "admin@puskesmas.com",
    "role": "admin",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

## 📊 Dashboard

### Get Dashboard Statistics
**Endpoint:** `GET /api/dashboard`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "total_items": 45,
    "total_stock": 1250,
    "low_stock_count": 5,
    "categories_count": 8,
    "recent_stock_in": [
      {
        "id": 1,
        "item_id": 1,
        "user_id": 1,
        "quantity": 100,
        "date": "2024-01-15",
        "notes": "Pembelian rutin",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "item": {
          "id": 1,
          "name": "Paracetamol 500mg",
          "unit": {
            "name": "Box",
            "symbol": "Box"
          }
        },
        "user": {
          "name": "Admin Puskesmas"
        }
      }
    ],
    "recent_stock_out": [
      {
        "id": 1,
        "item_id": 2,
        "user_id": 1,
        "quantity": 20,
        "date": "2024-01-16",
        "notes": "Untuk Poli Umum",
        "created_at": "2024-01-16T14:20:00.000000Z",
        "item": {
          "id": 2,
          "name": "Amoxicillin 500mg",
          "unit": {
            "name": "Strip",
            "symbol": "Strip"
          }
        },
        "user": {
          "name": "Admin Puskesmas"
        }
      }
    ],
    "low_stock_items": [
      {
        "id": 3,
        "code": "OBT003",
        "name": "Vitamin C 100mg",
        "stock": 5,
        "minimum_stock": 20,
        "category": {
          "name": "Obat-obatan"
        },
        "unit": {
          "name": "Botol",
          "symbol": "Btl"
        }
      }
    ]
  }
}
```

---

## 📂 Categories

### 1. Get All Categories
**Endpoint:** `GET /api/categories`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (optional): Search by name
- `page` (optional): Page number for pagination

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Obat-obatan",
        "description": "Kategori untuk obat-obatan umum",
        "items_count": 25,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
      },
      {
        "id": 2,
        "name": "Alat Kesehatan",
        "description": "Kategori untuk alat kesehatan",
        "items_count": 15,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "per_page": 10,
    "total": 8
  }
}
```

---

### 2. Get Single Category
**Endpoint:** `GET /api/categories/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Obat-obatan",
    "description": "Kategori untuk obat-obatan umum",
    "items_count": 25,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

### 3. Create Category
**Endpoint:** `POST /api/categories`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Vitamin & Suplemen",
  "description": "Kategori untuk vitamin dan suplemen kesehatan"
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Kategori berhasil dibuat",
  "data": {
    "id": 9,
    "name": "Vitamin & Suplemen",
    "description": "Kategori untuk vitamin dan suplemen kesehatan",
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T10:30:00.000000Z"
  }
}
```

**Response Error (422):**
```json
{
  "message": "The name field is required.",
  "errors": {
    "name": [
      "Nama kategori harus diisi"
    ]
  }
}
```

---

### 4. Update Category
**Endpoint:** `PUT /api/categories/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Vitamin & Suplemen",
  "description": "Kategori untuk vitamin, mineral, dan suplemen kesehatan"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Kategori berhasil diupdate",
  "data": {
    "id": 9,
    "name": "Vitamin & Suplemen",
    "description": "Kategori untuk vitamin, mineral, dan suplemen kesehatan",
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T11:00:00.000000Z"
  }
}
```

---

### 5. Delete Category
**Endpoint:** `DELETE /api/categories/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Kategori berhasil dihapus"
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Kategori tidak dapat dihapus karena masih memiliki barang"
}
```

---

## 📦 Items (Barang)

### 1. Get All Items
**Endpoint:** `GET /api/items`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (optional): Search by name or code
- `category_id` (optional): Filter by category
- `low_stock` (optional): Filter low stock items (1 or 0)
- `page` (optional): Page number

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "category_id": 1,
        "unit_id": 1,
        "code": "OBT001",
        "name": "Paracetamol 500mg",
        "description": "Obat penurun panas dan pereda nyeri",
        "stock": 150,
        "minimum_stock": 50,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "category": {
          "id": 1,
          "name": "Obat-obatan"
        },
        "unit": {
          "id": 1,
          "name": "Box",
          "symbol": "Box"
        }
      }
    ],
    "per_page": 10,
    "total": 45
  }
}
```

---

### 2. Get Single Item
**Endpoint:** `GET /api/items/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "category_id": 1,
    "unit_id": 1,
    "code": "OBT001",
    "name": "Paracetamol 500mg",
    "description": "Obat penurun panas dan pereda nyeri",
    "stock": 150,
    "minimum_stock": 50,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z",
    "category": {
      "id": 1,
      "name": "Obat-obatan"
    },
    "unit": {
      "id": 1,
      "name": "Box",
      "symbol": "Box"
    },
    "stock_ins": [],
    "stock_outs": []
  }
}
```

---

### 3. Create Item
**Endpoint:** `POST /api/items`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "category_id": 1,
  "unit_id": 1,
  "code": "OBT010",
  "name": "Amoxicillin 500mg",
  "description": "Antibiotik untuk infeksi bakteri",
  "stock": 0,
  "minimum_stock": 30
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Barang berhasil dibuat",
  "data": {
    "id": 46,
    "category_id": 1,
    "unit_id": 1,
    "code": "OBT010",
    "name": "Amoxicillin 500mg",
    "description": "Antibiotik untuk infeksi bakteri",
    "stock": 0,
    "minimum_stock": 30,
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T10:30:00.000000Z",
    "category": {
      "id": 1,
      "name": "Obat-obatan"
    },
    "unit": {
      "id": 1,
      "name": "Box",
      "symbol": "Box"
    }
  }
}
```

---

### 4. Update Item
**Endpoint:** `PUT /api/items/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "category_id": 1,
  "unit_id": 1,
  "code": "OBT010",
  "name": "Amoxicillin 500mg",
  "description": "Antibiotik untuk infeksi bakteri (Updated)",
  "minimum_stock": 40
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Barang berhasil diupdate",
  "data": {
    "id": 46,
    "category_id": 1,
    "unit_id": 1,
    "code": "OBT010",
    "name": "Amoxicillin 500mg",
    "description": "Antibiotik untuk infeksi bakteri (Updated)",
    "stock": 0,
    "minimum_stock": 40,
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T11:00:00.000000Z",
    "category": {
      "id": 1,
      "name": "Obat-obatan"
    },
    "unit": {
      "id": 1,
      "name": "Box",
      "symbol": "Box"
    }
  }
}
```

---

### 5. Delete Item
**Endpoint:** `DELETE /api/items/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Barang berhasil dihapus"
}
```

---

## ⬇️ Stock In (Barang Masuk)

### 1. Get All Stock In
**Endpoint:** `GET /api/stock-in`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `item_id` (optional): Filter by item
- `start_date` (optional): Filter from date (Y-m-d)
- `end_date` (optional): Filter to date (Y-m-d)
- `page` (optional): Page number

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "item_id": 1,
        "user_id": 1,
        "quantity": 100,
        "date": "2024-01-15",
        "notes": "Pembelian rutin",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "item": {
          "id": 1,
          "name": "Paracetamol 500mg",
          "category": {
            "name": "Obat-obatan"
          },
          "unit": {
            "name": "Box",
            "symbol": "Box"
          }
        },
        "user": {
          "id": 1,
          "name": "Admin Puskesmas"
        }
      }
    ],
    "per_page": 10,
    "total": 50
  }
}
```

---

### 2. Create Stock In
**Endpoint:** `POST /api/stock-in`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "item_id": 1,
  "quantity": 50,
  "date": "2024-01-20",
  "notes": "Pembelian dari supplier A"
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Barang masuk berhasil ditambahkan",
  "data": {
    "id": 51,
    "item_id": 1,
    "user_id": 1,
    "quantity": 50,
    "date": "2024-01-20",
    "notes": "Pembelian dari supplier A",
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T10:30:00.000000Z",
    "item": {
      "id": 1,
      "name": "Paracetamol 500mg",
      "stock": 200,
      "category": {
        "name": "Obat-obatan"
      },
      "unit": {
        "name": "Box",
        "symbol": "Box"
      }
    },
    "user": {
      "id": 1,
      "name": "Admin Puskesmas"
    }
  }
}
```

---

### 3. Update Stock In
**Endpoint:** `PUT /api/stock-in/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "item_id": 1,
  "quantity": 60,
  "date": "2024-01-20",
  "notes": "Pembelian dari supplier A (Updated)"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Barang masuk berhasil diupdate",
  "data": {
    "id": 51,
    "item_id": 1,
    "user_id": 1,
    "quantity": 60,
    "date": "2024-01-20",
    "notes": "Pembelian dari supplier A (Updated)",
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T11:00:00.000000Z"
  }
}
```

---

### 4. Delete Stock In
**Endpoint:** `DELETE /api/stock-in/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Barang masuk berhasil dihapus"
}
```

---

## ⬆️ Stock Out (Barang Keluar)

### 1. Get All Stock Out
**Endpoint:** `GET /api/stock-out`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `item_id` (optional): Filter by item
- `start_date` (optional): Filter from date (Y-m-d)
- `end_date` (optional): Filter to date (Y-m-d)
- `page` (optional): Page number

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "item_id": 2,
        "user_id": 1,
        "quantity": 20,
        "date": "2024-01-16",
        "notes": "Untuk Poli Umum",
        "created_at": "2024-01-16T14:20:00.000000Z",
        "updated_at": "2024-01-16T14:20:00.000000Z",
        "item": {
          "id": 2,
          "name": "Amoxicillin 500mg",
          "category": {
            "name": "Obat-obatan"
          },
          "unit": {
            "name": "Strip",
            "symbol": "Strip"
          }
        },
        "user": {
          "id": 1,
          "name": "Admin Puskesmas"
        }
      }
    ],
    "per_page": 10,
    "total": 35
  }
}
```

---

### 2. Create Stock Out
**Endpoint:** `POST /api/stock-out`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "item_id": 1,
  "quantity": 25,
  "date": "2024-01-20",
  "notes": "Untuk Poli Gigi"
}
```

**Response Success (201):**
```json
{
  "success": true,
  "message": "Barang keluar berhasil ditambahkan",
  "data": {
    "id": 36,
    "item_id": 1,
    "user_id": 1,
    "quantity": 25,
    "date": "2024-01-20",
    "notes": "Untuk Poli Gigi",
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T10:30:00.000000Z",
    "item": {
      "id": 1,
      "name": "Paracetamol 500mg",
      "stock": 175,
      "category": {
        "name": "Obat-obatan"
      },
      "unit": {
        "name": "Box",
        "symbol": "Box"
      }
    },
    "user": {
      "id": 1,
      "name": "Admin Puskesmas"
    }
  }
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Stok tidak mencukupi. Stok tersedia: 10 Box"
}
```

---

### 3. Update Stock Out
**Endpoint:** `PUT /api/stock-out/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "item_id": 1,
  "quantity": 30,
  "date": "2024-01-20",
  "notes": "Untuk Poli Gigi (Updated)"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Barang keluar berhasil diupdate",
  "data": {
    "id": 36,
    "item_id": 1,
    "user_id": 1,
    "quantity": 30,
    "date": "2024-01-20",
    "notes": "Untuk Poli Gigi (Updated)",
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T11:00:00.000000Z"
  }
}
```

---

### 4. Delete Stock Out
**Endpoint:** `DELETE /api/stock-out/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Barang keluar berhasil dihapus"
}
```

---

## 🔐 Authorization

Semua endpoint (kecuali `/login`) memerlukan authentication menggunakan Bearer Token.

**Header Format:**
```
Authorization: Bearer {your_token_here}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

## ⚠️ Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "Error message here"
    ]
  }
}
```

### Not Found (404)
```json
{
  "message": "No query results for model [App\\Models\\ModelName] {id}"
}
```

### Server Error (500)
```json
{
  "message": "Server Error",
  "exception": "Error details..."
}
```

---

## 📝 Notes

1. Semua response date menggunakan format ISO 8601: `YYYY-MM-DDTHH:mm:ss.000000Z`
2. Pagination menggunakan format Laravel default (current_page, per_page, total, dll)
3. Token expires sesuai konfigurasi Laravel Sanctum (default: tidak expired)
4. Stok otomatis terupdate saat create/update/delete stock_in dan stock_out
5. Validasi stok mencegah nilai negatif pada stock_out
