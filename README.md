# FotbalTesty Modern v2

Verzija prilagođena za Debian 12, rootless Podman 4.3 i `podman-compose` 1.0.x.

## Pokretanje

```bash
chmod +x start.sh scripts/diagnose.sh
./start.sh
```

Prvi start može dugo trajati jer se uvozi velika istorijska baza sa više stotina hiljada redova. Ne prekidajte proces dok MariaDB ne ispiše `ready for connections` i web servis `Database and schema are ready`.

Otvorite: `http://localhost:8081`

Demo nalog:

- e-mail: `demo@fotbaltesty.local`
- lozinka: `demo1234`

## Čist restart baze

```bash
podman-compose down -v
podman-compose build --no-cache
podman-compose up
```

Poruke da nepostojeći kontejner ne može biti obrisan mogu se zanemariti.

## Dijagnostika

```bash
./scripts/diagnose.sh
```

Za ručni pregled:

```bash
podman ps -a
podman-compose logs --tail=150 db
podman-compose logs --tail=150 web
```


## Popravka u v3

Ispravljena je SQL sintaksa za kreiranje demo naloga na MariaDB 11.4.
Bit vrednosti se sada upisuju kao `1` i `0`, a tekstualna polja koriste PDO parametre.
Podrazumevani web port je `8081`.
