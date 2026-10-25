# Status projektu

**Data aktualizacji:** 2026-10-25  
**Aktualna wersja stabilna:** `v1.1.0`  
**Stan testów i jakości:** 🟢 Wszystkie testy (66/66) i kontrole (PHPStan lvl 5, PHP-CS-Fixer PSR-12) zielone  
**Wydanie GitHub:** https://github.com/mafio69/php-logger/releases/tag/v1.1.0  
**Packagist:** https://packagist.org/packages/mafio69/fast-php-logger  

---

## Ostatnio zakończone
- [x] Przygotowanie i publikacja oficjalnego wydania `v1.1.0` (SemVer).
- [x] Dodanie GitHub Actions release workflow z automatycznym budowaniem `dist/fast-php-logger.php`.
- [x] Dodanie statycznej analizy kodu (PHPStan level 5) i formatowania PSR-12 (PHP-CS-Fixer).
- [x] Dodanie `.editorconfig` oraz `.gitattributes` (czyste paczki w Composerze).
- [x] Modernizacja `README.md` (badges, wymagania PHP >=8.1, ujednolicone przykłady, instrukcja dla Grafana Loki).
- [x] Uporządkowanie i dostosowanie reguł pracy agentów w `GIT-FLOW.md`.

---

## Następny priorytet (Kolejne zadanie: v1.2.0)

### 🎯 Zadanie: Structured logging (Format JSON) — punkt 3 z ROADMAP.md
- **Cel:** Natywny format wyjściowy JSON dla agregatorów logów (Grafana Loki, Promtail, ELK, Datadog).
- **Zakres:**
  - Dodanie enum/stałych `OutputFormat::TEXT` (domyślny) oraz `OutputFormat::JSON` do `LoggerConfigDto`.
  - Formatowanie każdego wpisu jako jedno-linijkowy JSON (NDJSON) z polami:
    `timestamp`, `level`, `caller`, `message`, `context`.
  - Przekazywanie zanonimizowanego i bezpiecznie zserializowanego kontekstu jako zagnieżdżonego obiektu JSON.
  - Testy jednostkowe i integracyjne dla formatu JSON.
  - Zaktualizowanie `dist/fast-php-logger.php` oraz dokumentacji.

---

## Stan pozostałych punktów z ROADMAP.md

| Punkt | Nazwa | Status | Wersja docelowa |
|-------|-------|--------|-----------------|
| 1 | Log levels per output (osobny level dla STDERR i pliku) | ⏳ Zaplanowane | v1.3.0 |
| 2 | Multiple file handlers (np. osobny plik dla error, osobny dla debug) | ⏳ Zaplanowane | v1.4.0 |
| 3 | Structured logging (Format JSON pod Grafanę/ELK) | 🚀 W toku / Następne | v1.2.0 |
| 4 | Custom formatters (pluggable formatters) | ⏳ Zaplanowane | v1.5.0 |
| 5 | Async / buffered writes (buforowane zapisy) | ⏳ Zaplanowane | v1.6.0 |
| 6 | Handler registry (rejestr handlerów) | ⏳ Zaplanowane | v1.7.0 |
| 7 | Context inheritance (globalny request_id, user_id) | ⏳ Zaplanowane | v1.8.0 |
| 8 | Log channels (kanały logowania: auth, db itp.) | ⏳ Zaplanowane | v1.9.0 |
| 9 | Performance metrics (timery i pomiary czasu) | ⏳ Zaplanowane | v1.10.0 |
| 10 | Documentation (diagramy przepływu i API docs) | 🔄 W trakcie | Ciągłe |
