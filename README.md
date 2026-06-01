# LV4 - PHP/MySQL (Filmovi + Ocjenjivanje fotografija)

Ovaj projekt implementira obavezne zahtjeve iz LV4 za:
- Zadatak 1(a): virtualna videoteka filmova
- Zadatak 2: ocjenjivanje fotografija

## Pokretanje

1. Pokreni `Apache` i `MySQL` (npr. kroz XAMPP).
2. Projekt mapu postavi u web root (`htdocs`) ili koristi lokalni PHP server.
3. Uredi konekciju po potrebi u [includes/db.php](C:/Users/User/Desktop/web lv 4/web-lv4/includes/db.php).
4. Otvori [install.php](C:/Users/User/Desktop/web lv 4/web-lv4/install.php) u pregledniku za kreiranje baze, tablica i demo korisnika.
5. Nakon instalacije otvori [login.php](C:/Users/User/Desktop/web lv 4/web-lv4/login.php).

## Demo korisnici

- Admin: `admin / admin123`
- Korisnik: `korisnik / korisnik123`

## Glavne funkcionalnosti

- Registracija i prijava korisnika (hashirane lozinke)
- Uloge korisnik/admin
- CRUD filmova (admin)
- Filtriranje i sortiranje filmova (server-side SQL)
- Košarica za odabir filmova i checkout posudbe
- Osobna videoteka (trajna pohrana po korisniku tek nakon checkouta)
- Upozorenje za filmove s niskom ocjenom (< 5.0)
- Galerija fotografija i upload (JPEG/PNG, do 5MB)
- Ocjenjivanje slika 1-5 s trajnom pohranom i ažuriranjem postojeće ocjene
- Prikaz prosječne ocjene po slici
- CSRF zaštita za sve POST akcije
- Prepared statements za sve upite s korisničkim unosom

## Brza provjera

1. Registracija / prijava / odjava.
2. Kao admin: dodavanje, uređivanje i brisanje filmova.
3. Kao korisnik: filtriranje filmova i dodavanje/uklanjanje iz osobne videoteke.
4. Dodavanje filma s ocjenom `< 5.0` i provjera upozorenja.
5. Upload JPEG/PNG i odbijanje datoteke > 5MB.
6. Ocijeni sliku, pa ponovno ocijeni istu sliku drugom ocjenom (mora ažurirati postojeći zapis).

## SQL izvoz

Struktura i seed podaci su u [database.sql](C:/Users/User/Desktop/web lv 4/web-lv4/database.sql).
Datoteka je spremna za predaju u repozitorij zajedno s kodom.
