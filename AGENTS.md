# AGENTS.md — globalne zasady pracy agentów AI

Ten plik obowiązuje każdego agenta AI uruchomionego lokalnie lub zdalnie, w tym OpenCode, Claude Code oraz agentów wywoływanych przez IDE, CLI, rozszerzenia i MCP. Obowiązuje niezależnie od katalogu, repozytorium, języka i rodzaju zadania.

## 1. Tryb pracy

- Odpowiadaj i pytaj użytkownika po polsku, o ile użytkownik wyraźnie nie poprosi o inny język.
- Stosuj zasadę najmniejszych uprawnień. Nie proś o dostęp, którego nie potrzebujesz do bieżącego zadania.
- Nie uruchamiaj trybu automatycznego zatwierdzania uprawnień (`--auto`, auto-approve ani odpowiednika).
- Nie uruchamiaj subagentów ani agentów pomocniczych bez wyraźnej zgody użytkownika.
- Nie wysyłaj kodu, danych, plików ani fragmentów konfiguracji do internetu, zewnętrznego API, repozytorium lub usługi bez osobnej zgody użytkownika.
- Nie zmieniaj konfiguracji agenta, plików uprawnień ani tego pliku bez wyraźnej prośby użytkownika.

## 2. Sekrety i dane wrażliwe — zakaz bez wyjątków

Obowiązuje absolutny zakaz czytania, przeszukiwania, kopiowania, dekodowania, wyświetlania, przesyłania i modyfikowania sekretów — każdym narzędziem, również Bash.

Za dane chronione uznaje się w szczególności:

- `.env`, `.env.*`, `.env.keys`, katalogi `secret/` i `.local/secret/`
- `~/.ssh/**`, `~/.aws/**`, `~/.config/gcloud/**`, `~/.kube/**`, `~/.gnupg/**`
- klucze i certyfikaty: `*.pem`, `*.key`, `*.p12`, `*.pfx`, `*.jks`, `id_rsa*`, `id_ed25519*`
- pliki i nazwy zawierające `secret`, `token`, `credential`, `client_secret`, `service-account`
- hasła, tokeny API, ciasteczka sesyjne, nagłówki Authorization, dane logowania, prywatne adresy, kopie baz danych oraz dane osobowe.

Zakazane są także polecenia lub ich warianty, które mogą ujawnić sekret, między innymi:

- `cat`, `less`, `more`, `head`, `tail`, `strings`, `xxd`, `od`, `base64` użyte na chronionych plikach lub katalogach
- `env`, `printenv`, `export -p`, `set` i ich przekierowania lub filtrowanie
- `dotenvx get`, `dotenvx run`, `command -v`, `dotenvx decrypt` oraz używanie `.env.keys`
- `gcloud auth print-access-token`, `gcloud auth print-identity-token` i polecenia eksportujące poświadczenia
- kopiowanie sekretów do logów, testów, komunikatów błędów, commitów, PR-ów i odpowiedzi dla użytkownika.

Jeżeli zadanie wymaga sekretu, zatrzymaj się i poproś użytkownika o wykonanie czynności lokalnie. Nie proś o wklejenie sekretu do czatu ani terminala.

## 3. Granice katalogu roboczego

- Pracuj wyłącznie w aktualnym katalogu roboczym, czyli katalogu, z którego uruchomiono agenta, oraz w jego podkatalogach.
- Nie odczytuj, nie zapisuj, nie wyszukuj i nie listuj plików poza aktualnym katalogiem roboczym.
- Nie używaj ścieżek bezwzględnych, `~`, `$HOME`, `..`, `../`, `/tmp`, `/etc`, `/var`, `/usr`, `/opt`, `/home` ani innych ścieżek prowadzących poza katalog roboczy.
- Nie zmieniaj katalogu przez `cd` poza aktualny katalog roboczy.
- Nie skanuj systemu plików, katalogu domowego ani dysków w celu „znalezienia” plików, narzędzi, konfiguracji lub projektów.
- Wyjątek jest możliwy wyłącznie po wyraźnej zgodzie użytkownika dla wskazanej ścieżki i pojedynczej czynności.

## 4. Bash i polecenia systemowe

- Każde polecenie Bash wymaga uprzedniej zgody użytkownika. Nie zakładaj zgody na kolejne polecenia na podstawie wcześniejszej zgody.
- Przed prośbą o zgodę podaj pełne polecenie, jego cel, katalog wykonania oraz wpływ na pliki, sieć i system.
- Nie używaj `sudo`, `su`, `pkexec`, `doas`, `docker exec`, `kubectl exec`, SSH, SCP, RSYNC, montowania dysków ani poleceń zdalnych bez osobnej, jednoznacznej zgody użytkownika.
- Nie instaluj, nie usuwaj i nie aktualizuj pakietów, zależności, kontenerów, obrazów, rozszerzeń ani narzędzi bez zgody użytkownika.
- Nie uruchamiaj poleceń destrukcyjnych lub potencjalnie kosztownych bez wyraźnej zgody: `rm`, `find -delete`, `truncate`, `dd`, `mkfs`, `chmod -R`, `chown -R`, `git reset --hard`, `git clean`, `docker system prune`, masowych migracji i masowych zmian plików.
- Nie pobieraj ani nie wykonuj skryptów z internetu, zwłaszcza wzorca `curl ... | sh`, `wget ... | bash` lub podobnych.
- Nie uruchamiaj komend do rozpoznawania środowiska, lokalizacji binarek lub dystrybucji bez pytania. Dotyczy to także `command -v tree`, `command -v *`, `which *`, `type *`, `whereis *`, `locate *`, `find *`, `ls` poza katalogiem roboczym, `uname`, `hostnamectl`, `lsb_release`, `cat /etc/os-release` i podobnych.

## 5. Sieć i usługi zewnętrzne

- Dostęp do internetu, `webfetch`, `websearch`, API, MCP, GitHub, GitLab, Docker Hub, npm, Composer, PyPI i innych usług wymaga zgody użytkownika dla konkretnej czynności.
- Nie wykonuj `git push`, nie twórz issue, PR, release, tagu, commitu ani nie zmieniaj zdalnego repozytorium bez wyraźnej zgody użytkownika.
- Przed każdą operacją wysyłającą dane poza komputer poinformuj dokładnie, jakie dane i do jakiej usługi zostaną przekazane.

## 6. Edycja i jakość

- Przed zmianą kodu lub konfiguracji pokaż plan zmian i poczekaj na zgodę, gdy zmiana jest większa niż pojedyncza, oczywista poprawka.
- Nie zmieniaj plików ukrytych, `.git/**`, katalogów zależności (`vendor`, `node_modules`) ani plików sekretów.
- Po zmianie uruchamiaj wyłącznie testy lub walidację potrzebną do bieżącego zadania i tylko po zgodzie na Bash.
- Nie przeglądaj historii Git, nie uruchamiaj `git log`, `git show`, `git restore` ani `git blame` „na wszelki wypadek”. Najpierw zapytaj, czy użytkownik chce takiego sprawdzenia.
- Ogranicz liczbę poleceń i odczytów do minimum. Nie wykonuj szerokich skanów, gdy wystarczy jedno precyzyjne pytanie.

## 7. Bash — katalog i komendy rozpoznawcze

Zakaz obejmuje wszystkie polecenia Bash działające poza aktualnym katalogiem roboczym, również wtedy, gdy ścieżka jest ukryta w zmiennej, potoku, podstawieniu polecenia, skrypcie, aliasie, funkcji lub poleceniu złożonym. Nie obchodź tej zasady przez `cd`, `..`, `~`, `$HOME`, ścieżkę bezwzględną, dowiązanie symboliczne ani kontener/SSH.

Polecenia rozpoznawcze i lokalizujące zawsze wymagają uprzedniego pytania użytkownika, nawet jeśli pozornie są nieszkodliwe. W szczególności nie wolno uruchamiać bez pytania `command -v tree`, `command -v <nazwa>`, `which <nazwa>`, `type <nazwa>`, `whereis <nazwa>`, `locate <wzorzec>`, `find <ścieżka>` ani ich odpowiedników.
