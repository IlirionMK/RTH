# RTH

API oraz webowy panel administracyjny do zarządzania danymi dla aplikacji mobilnej.

## Stos technologiczny

| Warstwa | Technologia |
| --- | --- |
| API | PHP 8.6, Laravel 13, Sanctum, Scramble (OpenAPI) |
| Panel administracyjny | Vue 3, TypeScript, Vite, Pinia, Vue Router, Tailwind CSS 4, shadcn-vue |
| Aplikacja mobilna | wybiera zespół |
| Baza danych | PostgreSQL 19 |
| Serwer HTTP | nginx 1.31 |
| Środowisko | Docker Compose, Node.js 26 |
| CI | GitHub Actions |

## Struktura

```
RTH/
├── backend/            API (Laravel)
├── web/                panel administracyjny (Vue)
├── mobile/             aplikacja mobilna
├── docker/             konfiguracja nginx, PHP i PostgreSQL
├── docs/               wymagania i sprawozdanie
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
| Panel administracyjny | http://localhost:5173 |
| Mailpit | http://localhost:8025 |
| PostgreSQL | localhost:5433 |

## Kontrola jakości

```bash
docker compose exec php composer ci:check
docker compose exec web npm run ci:check
```

`composer ci:check` uruchamia Pint, Larastan i PHPUnit. `npm run ci:check` uruchamia oxlint,
ESLint, vue-tsc, Vitest i build. CI na GitHub Actions wykonuje te same polecenia.

## GitHub flow

1. Każde zadanie ma swoje issue.
2. Pracę prowadzimy na osobnej gałęzi, np. `12-logowanie-administratora`.
3. Zmiany trafiają do `main` tylko przez pull request.
4. Tytuł pull requesta zaczyna się od numeru issue, np. `#12 Logowanie administratora`.
5. Pull request wymaga code review innego członka zespołu i zielonego CI.
6. Pull request łączymy przez squash merge.
