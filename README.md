# Travel App (Rejseapp)

En simpel PHP webapplikation til at registrere og administrere rejsedestinationer. Applikationen bruger MariaDB til datalagring.

## Funktioner

- ✅ Tilføj rejsedestinationer med by, land, år og beskrivelse
- ✅ Vis alle rejsedestinationer i en tabel
- ✅ Sorter efter by, land eller år (klik på tabeloverskrifterne)
- ✅ Rediger eksisterende rejsedestinationer
- ✅ Slet rejsedestinationer (med bekræftelse)

## Krav

- PHP 7.4 eller nyere
- MariaDB/MySQL database
- Webserver (Apache/Nginx) eller PHP built-in server til udvikling

## Installation

### 1. Clone eller download projektet

```bash
git clone https://github.com/DIT-BRUGERNAVN/rejseapp.git
cd rejseapp
```

### 2. Opret database

Kør `database.sql` i din MariaDB/MySQL database for at oprette den nødvendige tabel:

```sql
-- Se database.sql filen
```

### 3. Konfigurer databaseforbindelsen

Kopier `config.php.example` til `config.php`:

```bash
cp config.php.example config.php
```

Rediger `config.php` og udfyld dine database credentials:

```php
define('DB_HOST', 'localhost');           // Din database host
define('DB_NAME', 'your_database_name');  // Din database navn
define('DB_USER', 'your_username');       // Dit database brugernavn
define('DB_PASS', 'your_password');       // Dit database kodeord
```

**VIGTIGT:** Commit aldrig `config.php` til git repository, da den indeholder følsomme credentials!

### 4. Upload til webserver

Upload alle filer (undtagen `config.php.example`) til din webserver. Husk at oprette `config.php` med dine faktiske database credentials.

### 5. Test applikationen

Åbn din browser og naviger til `index.php` på din webserver.

## Filstruktur

```
rejseapp/
├── index.php          # Hovedsiden med liste over rejser
├── add.php            # Formular til at tilføje nye rejser
├── edit.php           # Formular til at redigere eksisterende rejser
├── delete.php         # Bekræftelsesside til sletning af rejser
├── config.php         # Database konfiguration (opret selv baseret på config.php.example)
├── config.php.example # Template til config.php
├── style.css          # CSS styling
├── database.sql       # SQL script til at oprette database tabellen
└── README.md          # Denne fil
```

## Sikkerhed

- `config.php` er udelukket fra version control via `.gitignore`
- Brug altid PDO prepared statements for at undgå SQL injection
- Input validering på både client-side og server-side
- HTML escaping for at undgå XSS angreb

## Teknologier

- **Backend:** PHP 7.4+
- **Database:** MariaDB/MySQL
- **Styling:** CSS3
- **Database Access:** PDO med prepared statements

## Licens

Dette projekt er open source og tilgængeligt under [MIT License](LICENSE).

## Support

For spørgsmål eller problemer, opret et issue på GitHub repository.

