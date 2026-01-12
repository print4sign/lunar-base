# 📷 Product Images Workflow

## Overzicht

De afbeeldingen worden opgeslagen in een **herbruikbare storage directory** (`storage/app/public/scraped-products/`) en kunnen meerdere keren worden gebruikt, zelfs als je producten verwijdert en opnieuw importeert.

## 🎯 Voordelen

- ✅ **Geen dubbele downloads** - Afbeeldingen worden maar één keer gedownload
- ✅ **Herbruikbaar** - Verwijder producten uit database, importeer opnieuw zonder downloads
- ✅ **Sneller importeren** - Bestaande afbeeldingen worden direct gebruikt
- ✅ **Minder bandbreedte** - Bespaar op data en tijd

## 📁 Storage Locatie

```
storage/
└── app/
    └── public/
        └── scraped-products/          # Alle product afbeeldingen
            ├── product-name-1.jpg
            ├── product-name-2.jpg
            └── ...
```

De afbeeldingen zijn publiek toegankelijk via: `http://your-domain.com/storage/scraped-products/`

## 🚀 Workflow Opties

### **Optie 1: Download Alle Afbeeldingen Vooraf** (Aanbevolen)

Download eerst alle afbeeldingen naar storage, dan importeer producten zonder downloads.

```bash
# Stap 1: Download alle afbeeldingen (eenmalig)
php artisan lunar:scraper:download-images --all

# Stap 2: Importeer producten (gebruikt bestaande afbeeldingen)
php artisan lunar:scraper:import --all --download-images
```

**Voordelen:**
- Alle downloads gebundeld in één keer
- Duidelijk overzicht van download progress
- Importeren gaat daarna supersnel

### **Optie 2: Download Tijdens Import**

Laat het import proces de afbeeldingen downloaden (zoals nu).

```bash
php artisan lunar:scraper:import --all --download-images
```

De importer checkt automatisch of afbeeldingen al lokaal bestaan. Als ze er zijn, worden ze direct gebruikt zonder opnieuw te downloaden.

### **Optie 3: Herbruik Na Database Reset**

Als je producten verwijdert maar de afbeeldingen behoudt:

```bash
# Verwijder producten uit database
php artisan db:seed --class=DatabaseSeeder --force

# Importeer opnieuw (gebruikt bestaande afbeeldingen!)
php artisan lunar:scraper:import --all --download-images
```

De afbeeldingen in `storage/app/public/scraped-products/` blijven bestaan en worden opnieuw gebruikt.

## 📋 Command Opties

### `lunar:scraper:download-images`

Download alle product afbeeldingen van JSON files naar herbruikbare storage.

```bash
# Download afbeeldingen voor alle producten
php artisan lunar:scraper:download-images --all

# Download afbeeldingen voor specifiek product
php artisan lunar:scraper:download-images --file=product-name

# Skip afbeeldingen die al lokaal bestaan
php artisan lunar:scraper:download-images --all --skip-existing

# Update JSON files met lokale image paths
php artisan lunar:scraper:download-images --all --update-json

# Combinatie: download + update JSON + skip bestaande
php artisan lunar:scraper:download-images --all --update-json --skip-existing

# Inclusief mockup afbeeldingen (standaard worden ze overgeslagen)
php artisan lunar:scraper:download-images --all --no-skip-mockups
```

**Output voorbeeld:**
```
Downloading images from 450 product(s)...
Storage location: storage/app/public/scraped-products/

 450/450 [============================] 100%

Download complete!
+--------------------------+-------+
| Metric                   | Count |
+--------------------------+-------+
| Total images found       | 1847  |
| Downloaded               | 1623  |
| Skipped (existing/mockups)| 189   |
| Failed                   | 35    |
| JSON files updated       | 450   |
+--------------------------+-------+

Images are stored in: storage/app/public/scraped-products/
Updated 450 JSON file(s) with local image paths.
These images will be reused when importing products.
```

### `lunar:scraper:import` met images

```bash
# Importeer met afbeeldingen (gebruikt bestaande waar mogelijk)
php artisan lunar:scraper:import --all --download-images

# Skip al geïmporteerde producten
php artisan lunar:scraper:import --all --download-images --skip-imported
```

## 🔄 Hoe het Werkt

### Bij Download (`download-images`)

1. Leest alle JSON files in `storage/app/scraper/`
2. Voor elke afbeelding:
   - Genereert bestandsnaam: `{product-slug}-{index}.{ext}`
   - Checkt of bestand al bestaat in `storage/app/public/scraped-products/`
   - Download alleen als bestand **niet bestaat**
   - Slaat mockup afbeeldingen standaard over
3. Met `--update-json` optie:
   - Update JSON files met lokale image URLs
   - Behoudt originele URL in `original_url` veld
   - Voegt `local_path` veld toe met bestandsnaam

### Bij Import (`import --download-images`)

1. Voor elke product afbeelding:
   - Genereert dezelfde bestandsnaam: `{product-slug}-{index}.{ext}`
   - Checkt `ImageDownloader->exists()` of bestand lokaal bestaat
   - Als **JA**: Gebruikt bestaand bestand, telt als "used_existing"
   - Als **NEE**: Download nieuw, telt als "downloaded"
   - Koppelt afbeelding aan product in media library

### Bestandsnaamconventie

Afbeeldingen krijgen consistente namen gebaseerd op product:

```
product-slug-1.jpg    # Eerste afbeelding
product-slug-2.jpg    # Tweede afbeelding
product-slug-3.png    # Derde afbeelding (kan andere extensie hebben)
```

Dit zorgt ervoor dat dezelfde afbeelding altijd dezelfde naam heeft, ongeacht wanneer je importeert.

### JSON Structuur na `--update-json`

**Voor update:**
```json
{
  "name": "3M IJ35C Wit",
  "images": [
    "https://www.probo.nl/media/catalog/product/3/m/3m-ij35c-wit.jpg",
    {
      "url": "https://www.probo.nl/media/catalog/product/3/m/3m-ij35c-detail.jpg",
      "alt": "Detail foto"
    }
  ]
}
```

**Na update:**
```json
{
  "name": "3M IJ35C Wit",
  "images": [
    "http://localhost/storage/scraped-products/3m-ij35c-wit-1.jpg",
    {
      "url": "http://localhost/storage/scraped-products/3m-ij35c-wit-2.jpg",
      "alt": "Detail foto",
      "local_path": "3m-ij35c-wit-2.jpg",
      "original_url": "https://www.probo.nl/media/catalog/product/3/m/3m-ij35c-detail.jpg"
    }
  ]
}
```

**Voordelen:**
- ✅ Import gebruikt automatisch lokale afbeeldingen
- ✅ Originele URLs bewaard voor referentie
- ✅ Bestandsnamen opgeslagen voor eenvoudige lookup
- ✅ JSON blijft compatibel met oude en nieuwe imports

## 🗑️ Storage Beheer

### Afbeeldingen bekijken

```bash
# Toon alle afbeeldingen
ls -lh storage/app/public/scraped-products/

# Tel aantal afbeeldingen
ls storage/app/public/scraped-products/ | wc -l

# Bekijk totale grootte
du -sh storage/app/public/scraped-products/
```

### Afbeeldingen opruimen

```bash
# Verwijder alle gedownloade afbeeldingen
rm -rf storage/app/public/scraped-products/*

# Verwijder alleen oude afbeeldingen (niet gebruikt in laatste 30 dagen)
find storage/app/public/scraped-products/ -type f -mtime +30 -delete
```

### Backup maken

```bash
# Maak backup van alle afbeeldingen
tar -czf product-images-backup-$(date +%Y%m%d).tar.gz storage/app/public/scraped-products/

# Herstel backup
tar -xzf product-images-backup-20260111.tar.gz
```

## ⚙️ Configuratie

De image storage is geconfigureerd in `config/lunar/scraper.php`:

```php
'images' => [
    'disk' => 'public',                    // Laravel storage disk
    'directory' => 'scraped-products',     // Directory binnen disk
],
```

Je kunt dit aanpassen naar je eigen behoeften (bijvoorbeeld S3 bucket).

## 🎯 Best Practices

### Voor Productie (Aanbevolen Workflow)

1. **Eenmalige setup - Download & Update JSON:**
   ```bash
   # Download alle afbeeldingen EN update JSON files met lokale paths
   php artisan lunar:scraper:download-images --all --update-json
   ```

2. **Eerste import:**
   ```bash
   # Importeer producten (gebruikt lokale paths uit JSON)
   php artisan lunar:scraper:import --all --download-images
   ```

3. **Updates/Re-imports:**
   ```bash
   # Skip al geïmporteerde producten, herbruik afbeeldingen
   php artisan lunar:scraper:import --all --download-images --skip-imported
   ```

### Alternatieve Workflow (Zonder JSON Update)

1. **Download afbeeldingen:**
   ```bash
   php artisan lunar:scraper:download-images --all --skip-existing
   ```

2. **Import (ImageDownloader checkt automatisch op lokale bestanden):**
   ```bash
   php artisan lunar:scraper:import --all --download-images
   ```

### Voor Development

1. **Test met kleine set:**
   ```bash
   # Download afbeeldingen voor 10 producten
   php artisan lunar:scraper:download-images --all --skip-existing | head -10

   # Importeer deze producten
   php artisan lunar:scraper:import --all --download-images
   ```

2. **Reset en herhaal:**
   ```bash
   # Verwijder producten, behoud afbeeldingen
   php artisan migrate:fresh --seed

   # Re-importeer (gebruikt bestaande afbeeldingen, zeer snel!)
   php artisan lunar:scraper:import --all --download-images
   ```

## 📊 Performance

### Download Snelheid

- **Eerste keer**: ~2-5 seconden per afbeelding (afhankelijk van grootte en verbinding)
- **Met bestaande afbeeldingen**: <0.01 seconden per afbeelding (alleen database koppeling)

### Voorbeeld Tijdsbesparing

Voor 500 producten met gemiddeld 4 afbeeldingen elk (2000 afbeeldingen):

| Scenario | Tijd | Bandbreedte |
|----------|------|-------------|
| Eerste download | ~2-3 uur | ~2-5 GB |
| Re-import met bestaande afbeeldingen | ~5-10 minuten | ~0 MB |

**Besparing bij re-import: 95%+ tijd en 100% bandbreedte!** 🎉

## 🔍 Troubleshooting

### "Image already exists locally, skipping download"

✅ Dit is normaal! De afbeelding is al gedownload en wordt herbruikt.

### Afbeelding niet gevonden bij import

Mogelijke oorzaken:
1. Product naam is veranderd (andere slug)
2. Afbeeldingen zijn verwijderd uit storage
3. Import gebruikt andere bestandsnaam

**Oplossing:**
```bash
# Download afbeeldingen opnieuw
php artisan lunar:scraper:download-images --all
```

### Te weinig diskruimte

**Oplossing:**
```bash
# Verwijder oude/ongebruikte afbeeldingen
rm -rf storage/app/public/scraped-products/*

# Download alleen wat je nodig hebt
php artisan lunar:scraper:download-images --file=specific-product
```

## 📝 Loggen

Alle afbeelding operaties worden gelogd in `storage/logs/laravel.log`:

```bash
# Bekijk recente download logs
tail -f storage/logs/laravel.log | grep "Downloaded image"

# Bekijk welke afbeeldingen werden herbruikt
tail -f storage/logs/laravel.log | grep "already exists locally"
```

## 🎓 Samenvatting

**TL;DR:**
1. Download afbeeldingen & update JSON: `php artisan lunar:scraper:download-images --all --update-json`
2. Importeer producten: `php artisan lunar:scraper:import --all --download-images`
3. Bij re-import worden afbeeldingen automatisch herbruikt (100x sneller!)
4. Afbeeldingen blijven bestaan in `storage/app/public/scraped-products/` zelfs als je producten verwijdert
5. JSON files bevatten nu lokale paths, zodat import direct de lokale afbeeldingen gebruikt

## 🆕 Nieuwe Features

### `--update-json` Optie

De `--update-json` optie biedt extra voordelen:

**✅ Wat het doet:**
- Update JSON files met lokale image URLs (`http://localhost/storage/scraped-products/...`)
- Behoudt originele Probo URL in `original_url` veld
- Voegt `local_path` veld toe met bestandsnaam
- Maakt JSON portable tussen environments (dev/staging/prod)

**✅ Wanneer te gebruiken:**
- Eerste keer afbeeldingen downloaden
- Na het verplaatsen van afbeeldingen naar nieuwe locatie
- Bij het voorbereiden van data voor productie deployment
- Als je wilt garanderen dat import altijd lokale afbeeldingen gebruikt

**✅ Voorbeeld gebruik:**
```bash
# Download + update JSON in één keer
php artisan lunar:scraper:download-images --all --update-json --skip-existing

# Importeer (gebruikt direct lokale paths uit JSON)
php artisan lunar:scraper:import --all --download-images
```
