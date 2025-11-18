# Dokument wymagań produktu (PRD) – easy-banking-system

## 1. Przegląd produktu
Projekt easy-banking-system ma na celu umożliwienie pracownikom banku zakładanie i zamykanie rachunków bankowych klientom, a klientom
zarządzanie środkami pieniężnymi na swoich rachunkach.

## 2. Problem użytkownika
Wykonywanie przelewów bankowych w oddziale banku wymaga czasu i wysiłku. Celem rozwiązania jest wygodne zarządzanie środkami przez klienta
poprzez aplikację webową.

## 3. Wymagania funkcjonalne
1. Zakładanie rachunków bankowych:
   - Użytkownik (pracownik banku) zakłada rachunek bankowy w wybranej walucie (PLN, EUR).
   - System generuje losowy (dostępny) numer IBAN.
   - Rachunek jest przypisywany do nowego lub istniejącego klienta.

2. Zamykanie rachunków bankowych:
   - Użytkownik (pracownik banku) zamyka (dezaktywuje) rachunek bankowy na wniosek klienta.
   - Bezpośrednio przed zamknięciem wykonywana jest wypłata znajdujących się na nim środków.

3. Wykonywanie przelewów:
   - Użytkownik (klient) wykonuje przelew w wybranej kwocie na rachunek o podanym numerze IBAN.
   - Przelew nie może być na kwotę większą, niż bieżące saldo.
   - W przypadku różnych walut następuje przewalutowanie według określonego kursu.

4. Historia transakcji:
   - Klient może przeglądać historię transakcji na swoich rachunkach.
   - Pracownik może przeglądać historię transakcji na wybranym rachunku wybranego klienta.

5. Podstawowy system uwierzytelniania i kont użytkowników:
   - Konto pracownika można założyć ręcznie poprzez komendę CLI.
   - Zalogować się mogą zarówno pracownicy jak i klienci poprzez wspólny formularz.

6. Przechowywanie i skalowalność:
   - Dane o rachunkach i użytkownikach przechowywane w sposób zapewniający skalowalność i bezpieczeństwo.

## 4. Granice produktu
1. Poza zakresem MVP:
   - dokonywanie wypłat w bankomacie
   - zakładanie kont pracownikom poprzez interfejs webowy
   - szczegółowe uprawnienia do wykonywania konkretnych akcji
   - przelewy międzybankowe
   - dezaktywacja kont użytkowników
   - inne transakcje poza przelewami wewnątrz banku i wypłatą gotówki

## 5. Historyjki użytkowników

ID: US-001
Tytuł: Rejestracja konta pracownika
Opis: Jestem nowym pracownikiem banku i potrzebuję konta, aby móc obsługiwać klientów.
Kryteria akceptacji:
- Komenda CLI przyjmuje imię, nazwisko i hasło.
- Po poprawnym podaniu danych konto jest aktywne.

ID: US-002
Tytuł: Logowanie do aplikacji przez pracownika
Opis: Jako zarejestrowany pracownik chcę móc się zalogować, aby mieć dostęp do zarządzania klientami i rachunkami.
Kryteria akceptacji:
- Po podaniu prawidłowych danych logowania użytkownik zostaje przekierowany do widoku panelu.
- Błędne dane logowania wyświetlają komunikat o nieprawidłowych danych.
- Dane dotyczące logowania przechowywane są w bezpieczny sposób.

ID: US-003
Tytuł: Zakładanie rachunku bankowego dla nowego klienta
Opis: Jestem zalogowanym pracownikiem banku i nowy klient chce założyć rachunek.
Kryteria akceptacji:
- W widoku tworzenia rachunku znajdują się pola tekstowe, w których pracownik podaje imię i nazwisko klienta oraz pole waluty.
- Pola tekstowe oczekują od 2 do 50 znaków.
- Pole waluty pozwala wybrać jedną z dwóch walut: PLN, EUR.
- Przy tworzeniu rachunku, jest on przypisywany do klienta.

ID: US-004
Tytuł: Zakładanie rachunku bankowego dla istniejącego klienta
Opis: Jestem zalogowanym pracownikiem banku i znany mi klient chce założyć kolejny rachunek.
Kryteria akceptacji:
- W widoku tworzenia rachunku znajduje się pole waluty.
- Pole waluty pozwala wybrać jedną z dwóch walut: PLN, EUR.
- Przy tworzeniu rachunku, jest on przypisywany do klienta.
- Jeśli klient jest nieaktywny, staje się aktywny.

ID: US-005
Tytuł: Zamykanie rachunku
Opis: Jestem zalogowanym pracownikiem banku i klient chce zamknąć jeden ze swoich rachunków.
Kryteria akceptacji:
- W przypadku dodatniego salda, cała kwota jest wypłacana gotówką.
- Po zamknięciu rachunek jest nieaktywny, jego saldo wynosi 0 i nie można na nim dokonywać transakcji.
- Rachunek pozostaje przypisany do klienta.
- Jeśli klient nie posiada już aktywnych rachunków, jego konto jest nieaktywne.

ID: US-006
Tytuł: Przegląd transakcji przez klienta
Opis: Jako zalogowany klient chcę móc przeglądać historię transakcji na moich rachunkach.
Kryteria akceptacji:
- Lista transakcji jest wyświetlana w panelu.
- Transakcje pochodzą ze wszystkich rachunków klienta.
- Lista transakcji jest posortowana według daty i godziny wykonania malejąco.

ID: US-007
Tytuł: Wykonanie przelewu przez klienta
Opis: Jako zalogowany klient chcę wykonać przelew.
Kryteria akceptacji:
- W widoku wykonania przelewu znajduje się pole tekstowe, w którym należy wpisać poprawny numer IBAN oraz pole tekstowe, w którym należy podać kwotę.
- Przelewy na rachunki nieistniejące w banku są odrzucane, a klient widzi komunikat o błędzie.
- Przelewy na kwotę wyższą niż saldo są odrzucane, a klient widzi komunikat o błędzie.
- Powstaje przelew wychodzący na jednym rachunku oraz przelew przychodzący na drugim rachunku.

ID: US-008
Tytuł: Wykonanie przelewu przez klienta na rachunek w innej walucie
Opis: Jako zalogowany klient chcę wykonać przelew na rachunek w innej walucie niż mój rachunek.
Kryteria akceptacji:
- Przelew przychodzący na drugim rachunku ma kwotę wynikającą z przewalutowania kwoty wyjściowej według określonego kursu.

ID: US-009
Tytuł: Bezpieczny dostęp i autoryzacja
Opis: Jako zalogowany klient chcę mieć pewność, że moje rachunki nie są dostępne dla innych klientów, aby zachować prywatność i bezpieczeństwo danych.
Kryteria akceptacji:
- Tylko zalogowany klient może wyświetlać swoje rachunki.
- Nie ma dostępu do rachunków innych klientów.
- Pracownik banku może zobaczyć rachunki każdego klienta.

## 6. Metryki sukcesu
1. Zaangażowanie:
   - Monitorowanie liczby aktywnych klientów oraz łącznej kwoty na wszystkich rachunkach i porównanie z liczbą zatwierdzonych do analizy jakości i użyteczności.
