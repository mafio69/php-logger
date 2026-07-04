# Notatka z błędu (Do bezwzględnego zapamiętania)

## 1. Czytaj ze zrozumieniem polecenia
Jeśli użytkownik prosi o **"propozycje rozwiązań"**, moim zadaniem jest przedstawić te propozycje "na sucho", a nie samowolnie modyfikować kod. Działanie bez pytania o zgodę niszczy zaufanie.

## 2. Złota zasada modyfikacji repozytorium (Git)
NIGDY nie wolno mi wykonywać komend typu `git restore`, `git reset`, `git checkout` bez wcześniejszego bezwzględnego sprawdzenia stanu repozytorium (`git status`, `git diff`). Ślepe użycie tych komend może bezpowrotnie usunąć niezacommitowaną, ciężką pracę użytkownika. Wycofanie moich własnych błędów musi odbywać się chirurgicznie na poziomie plików, chyba że mam 100% pewności, że repozytorium było czyste.

**Wniosek:** Myśl zanim wykonasz destrukcyjną komendę. Pracuj tak, aby nigdy nie ryzykować utraty danych użytkownika.
