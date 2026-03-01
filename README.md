<p align="center">
  <h1 align="center">📦 Cek Kurir Resi</h1>
  <p align="center">
    <strong>Indonesian Courier Tracking API Aggregator</strong>
  </p>
  <p align="center">
    Built with PHP 8.2 &bull; Clean Architecture &bull; .env Configuration
  </p>
</p>

---

## ✨ Features

- **8 couriers** supported out of the box
- **Single endpoint** — pass courier slug + tracking number, get JSON
- **Clean architecture** — interface-driven, one class per courier
- **Secure** — API keys in `.env`, never in source
- **Easy to extend** — add a new courier in 3 steps

## 🚚 Supported Couriers

| Slug | Courier | Website |
|------|---------|---------|
| `anteraja` | AnterAja | anteraja.id |
| `sicepat` | SiCepat | sicepat.com |
| `pos` | Pos Indonesia | posindonesia.co.id |
| `ninja` | NinjaXpress | ninjaxpress.co |
| `tiki` | TIKI | tiki.id |
| `linitrans` | LiniTrans | linitransslogistics.com |
| `lionparcel` | Lion Parcel | thelionparcel.com |
| `sapx` | SAP Express | sapx.id |

## 📋 Requirements

- PHP **8.2** or higher
- PHP cURL extension
- [Composer](https://getcomposer.org)

## 🚀 Getting Started

### 1. Install dependencies

```bash
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` and fill in your API keys/tokens.

### 3. Start the server

```bash
php -S localhost:8000 -t public
```

## 📡 API Usage

### Endpoint

```
GET /?kurir={slug}&resi={tracking_number}
```

### Example

```bash
curl "http://localhost:8000/?kurir=sicepat&resi=004150064737"
```

### Response (success)

```json
{
  "name": "SiCepat",
  "site": "sicepat.com",
  "error": false,
  "message": "success",
  "info": {
    "no_awb": "004150064737",
    "service": "SIUNT",
    "status": "DELIVERED",
    "tanggal_kirim": "15-01-2026 10:30",
    "tanggal_terima": "17-01-2026 14:22",
    "harga": 18000,
    "berat": 1,
    "catatan": null
  },
  "pengirim": { "nama": "JOHN", "phone": null, "alamat": "JAKARTA" },
  "penerima": { "nama": "JANE", "nama_penerima": "JANE", "phone": null, "alamat": "BANDUNG" },
  "history": [
    { "tanggal": "15-01-2026 10:30", "posisi": "Jakarta", "message": "[Jakarta] Paket diterima" }
  ]
}
```

### Response (error)

```json
{
  "name": null,
  "site": null,
  "error": true,
  "message": "Jasa pengiriman belum didukung!"
}
```

## 🏗️ Project Structure

```
cekresi/
├── public/
│   └── index.php                # Entry point
├── src/
│   ├── Config/
│   │   └── Config.php           # .env loader (singleton)
│   ├── Courier/
│   │   ├── CourierInterface.php  # Contract
│   │   ├── AbstractCourier.php  # Shared helpers
│   │   ├── CourierFactory.php   # Registry / factory
│   │   ├── AnterAja.php
│   │   ├── SiCepat.php
│   │   ├── PosIndonesia.php
│   │   ├── NinjaXpress.php
│   │   ├── Tiki.php
│   │   ├── LiniTrans.php
│   │   ├── LionParcel.php
│   │   └── SapX.php
│   ├── DTO/
│   │   ├── TrackingResult.php
│   │   ├── SimpleTrackingResult.php
│   │   ├── ShipmentInfo.php
│   │   ├── Sender.php
│   │   ├── Receiver.php
│   │   └── HistoryEntry.php
│   └── Http/
│       ├── CurlClient.php       # HTTP wrapper
│       ├── CurlResponse.php     # Response DTO
│       └── HttpMethod.php       # GET/POST enum
├── storage/                     # Runtime data (git-ignored)
├── .env                         # Secrets (git-ignored)
├── .env.example                 # Template (committed)
├── .htaccess                    # Apache rewrite rules
├── composer.json
└── README.md
```

## ➕ Adding a New Courier

1. **Create** a class in `src/Courier/` extending `AbstractCourier`
2. **Implement** the `track(string $resi): string` method
3. **Register** the slug in `CourierFactory::COURIER_MAP`
4. **Add** API keys to `.env` and `.env.example`

```php
// src/Courier/MyCourier.php
final class MyCourier extends AbstractCourier
{
    public function getName(): string { return 'My Courier'; }
    public function getSite(): string { return 'mycourier.com'; }
    public function getSlug(): string { return 'mycourier'; }

    public function track(string $resi): string
    {
        $response = $this->http->get("https://api.mycourier.com/track?awb={$resi}");
        // ... parse and return TrackingResult JSON
    }
}
```

## 📄 License

[MIT](LICENSE)
