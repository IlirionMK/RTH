# RTH

Aplikacja turystyczna — osobisty dziennik podróży. Użytkownik zapisuje odbyte podróże, ogląda je na
interaktywnej mapie i przegląda statystyki podróżnicze.

System składa się z API, aplikacji webowej i aplikacji mobilnej. Aplikacja webowa ma wszystkie funkcje
aplikacji mobilnej oraz panel administracyjny.

## Funkcjonalności

- Konta użytkowników: rejestracja, logowanie, edycja profilu, profil publiczny lub prywatny.
- Podróże z dowolną liczbą punktów i odcinków; środki transportu: samochód, samolot, pociąg, inne.
- Mapa pojedynczej podróży i wszystkich podróży, z automatycznie rozpoznanymi odwiedzonymi państwami.
- Statystyki dla całej historii i wybranego roku: podróże, państwa, miejsca, dystans według środka transportu.
- Przeglądanie podróży użytkowników z publicznym profilem.
- Panel administracyjny: konta, podróże, statystyki systemu.

Pełny opis zamówienia: [`docs/Projekt_zespolowy_2026.pdf`](docs/Projekt_zespolowy_2026.pdf).
Zadania: [GitHub Issues](https://github.com/IlirionMK/RTH/issues).

## Stos technologiczny

| Warstwa | Technologia |
| --- | --- |
| API | PHP 8.6, Laravel 13, Sanctum, Scramble (OpenAPI) |
| Aplikacja webowa | Vue 3, TypeScript, Vite, Pinia, Vue Router, Tailwind CSS 4, shadcn-vue |
| Aplikacja mobilna | wybiera zespół |
| Baza danych | PostgreSQL 19 |
| Serwer HTTP | nginx 1.31 |
| Środowisko | Docker Compose, Node.js 26 |
| CI | GitHub Actions |

## Struktura

```
RTH/
├── backend/            API (Laravel)
├── web/                aplikacja webowa i panel administracyjny (Vue)
├── mobile/             aplikacja mobilna
├── docker/             konfiguracja nginx, PHP i PostgreSQL
├── docs/               opis zamówienia, warunki zaliczenia, sprawozdanie
└── docker-compose.yml
```

## Uruchomienie lokalne

Wymagany jest Docker z Docker Compose.

```bash
cp backend/.env.example backend/.env
docker compose up -d --build
docker compose exec php composer setup
```

Kontener `web` sam instaluje zależności przy starcie.

| Serwis | Adres |
| --- | --- |
| API | http://localhost:8000/api |
| Dokumentacja OpenAPI | http://localhost:8000/docs/api |
| Aplikacja webowa | http://localhost:5173 |
| Mailpit | http://localhost:8025 |
| PostgreSQL | localhost:5433 |

## Kontrola jakości

```bash
docker compose exec php composer ci:check
docker compose exec web npm run ci:check
```

`composer ci:check` uruchamia Pint, Larastan i PHPUnit. `npm run ci:check` uruchamia oxlint,
ESLint, vue-tsc, Vitest i build. CI na GitHub Actions wykonuje te same polecenia i sprawdza, czy
tytuł pull requesta zaczyna się od numeru zadania.

## GitHub flow

1. Każde zadanie ma swoje issue.
2. Pracę prowadzimy na osobnej gałęzi, np. `2-podroze-punkty-odcinki`.
3. Zmiany trafiają do `main` tylko przez pull request.
4. Tytuł pull requesta zaczyna się od numeru issue, np. `#2 API: podróże, punkty i odcinki`.
5. Pull request wymaga code review innego członka zespołu i zielonego CI.
6. Pull request łączymy przez squash merge.
