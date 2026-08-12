## ⚡ Trzy Przykazania

### I. Prostota przede wszystkim
- Minimum kodu. Nie spekuluj.
- Brak funkcji poza żądaniem, brak abstrakcji dla kodu jednorazowego, brak "elastyczności" która nie była proszona.
- Jeśli 200 linii → 50 linii, przepisz.
- **Test:** Czy senior powiedziałby "skomplikowane"? Uprość.

### II. Chirurgiczne zmiany
- Dotykaj co musisz. Nie ulepszaj sąsiedniego kodu. Nie refaktoruj niepsutego.
- Pasuj do stylu. Martwego kodu: wspomnij, nie usuwaj. Usuń tylko swoje orphans.
- **Test:** Każda zmiana → bezpośrednio do wymagania.

### III. Wykonanie sterowane celem
- Zdefiniuj sukces. Pętluj aż działa.
- Zamiast "Dodaj walidację" → zmień na **"Testy invalid → pass"**
- Zamiast "Napraw błąd" → zmień na **"Test reprodukuje → pass"**
- Zamiast "Refaktoruj X" → zmień na **"Testy przed i po: pass"**
- Wieloetapowe: plan + weryfikacja:
  1. [Krok] → verify: [check]
  2. [Krok] → verify: [check]
  3. [Krok] → verify: [check]

**Motto: myśl, uprość, wykonaj precyzyjnie.**

---

## 🧬 Zasady Jakości Kodu

### 1. Jedna odpowiedzialność
- Do opisu metody/klasy **nie używamy "i" ("and")**.
- Jak jest "i" w opisie — rozbij na dwie rzeczy.
- Klasa robi jedno. Metoda robi jedno. Koniec.

### 2. If-y mogą być złem
- Wielopoziomowe, nieczytelne warunki niszczą kod.
- Wynik złożonego `&&` / `||` wyciągnij do **zmiennej z nazwą** lub **prywatnej metody**.
- Early return zamiast zagnieżdżania.
- **Test:** Czy warunek jest zrozumiały w 2 sekundy? Nie → uprość.

### 3. Cienkie kontrolery
- Kontroler **przyjmuje request i oddaje response**. Nic więcej.
- Logika biznesowa → serwis.
- Walidacja → serwis lub dedykowany validator.
- Kontroler to router, nie mózg.

### 4. SOLID / DRY — pragmatycznie
- Stosuj SOLID i DRY ale **bez dogmatyzmu**.
- Nie twórz abstrakcji "na przyszłość" — twórz gdy jest druga potrzeba.
- DRY = nie kopiuj logiki. Ale dwa podobne if-y to nie powód do wydzielania klasy.

### 5. Kod dla człowieka
- Musi być do ogarnięcia **jednym spojrzeniem**.
- Nazwy zmiennych/metod mówią CO robią, nie JAK.
- Jeśli musisz czytać 3 razy żeby zrozumieć — przepisz.
- Komentarz = porażka nazewnictwa. Wyjątek: "dlaczego" (nie "co").
