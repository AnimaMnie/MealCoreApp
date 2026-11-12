<?php

/*
 * Polskie komunikaty walidacji dla reguł używanych w projekcie.
 * Brakujące reguły Laravel bierze z lang/en (APP_FALLBACK_LOCALE).
 */
return [
    'accepted' => 'Pole :attribute musi zostać zaakceptowane.',
    'array' => 'Pole :attribute musi być tablicą.',
    'boolean' => 'Pole :attribute musi mieć wartość prawda lub fałsz.',
    'confirmed' => 'Potwierdzenie pola :attribute nie zgadza się.',
    'current_password' => 'Hasło jest nieprawidłowe.',
    'date' => 'Pole :attribute nie jest prawidłową datą.',
    'date_format' => 'Pole :attribute musi mieć format :format.',
    'different' => 'Pola :attribute i :other muszą się różnić.',
    'distinct' => 'Pole :attribute zawiera powtarzającą się wartość.',
    'email' => 'Pole :attribute musi być prawidłowym adresem e-mail.',
    'exists' => 'Wybrana wartość pola :attribute nie istnieje.',
    'in' => 'Wybrana wartość pola :attribute jest nieprawidłowa.',
    'integer' => 'Pole :attribute musi być liczbą całkowitą.',
    'max' => [
        'array' => 'Pole :attribute może mieć najwyżej :max elementów.',
        'numeric' => 'Pole :attribute nie może być większe niż :max.',
        'string' => 'Pole :attribute może mieć najwyżej :max znaków.',
    ],
    'min' => [
        'array' => 'Pole :attribute musi mieć co najmniej :min elementów.',
        'numeric' => 'Pole :attribute musi wynosić co najmniej :min.',
        'string' => 'Pole :attribute musi mieć co najmniej :min znaków.',
    ],
    'numeric' => 'Pole :attribute musi być liczbą.',
    'password' => [
        'letters' => 'Pole :attribute musi zawierać co najmniej jedną literę.',
        'mixed' => 'Pole :attribute musi zawierać małą i wielką literę.',
        'numbers' => 'Pole :attribute musi zawierać co najmniej jedną cyfrę.',
        'symbols' => 'Pole :attribute musi zawierać co najmniej jeden znak specjalny.',
        'uncompromised' => 'Podane :attribute pojawiło się w wycieku danych. Wybierz inne.',
    ],
    'present' => 'Pole :attribute musi być obecne.',
    'prohibited' => 'Pole :attribute jest niedozwolone.',
    'required' => 'Pole :attribute jest wymagane.',
    'required_if' => 'Pole :attribute jest wymagane, gdy :other ma wartość :value.',
    'required_with' => 'Pole :attribute jest wymagane, gdy obecne jest :values.',
    'same' => 'Pola :attribute i :other muszą być takie same.',
    'string' => 'Pole :attribute musi być tekstem.',
    'unique' => 'Taka wartość pola :attribute już istnieje.',

    'custom' => [],

    // Czytelne nazwy pól w komunikatach (zamiast "kcal 100g").
    'attributes' => [
        'name' => 'nazwa',
        'email' => 'e-mail',
        'password' => 'hasło',
        'role' => 'rola',
        'category' => 'kategoria',
        'kcal_100g' => 'kalorie (na 100 g)',
        'protein_100g' => 'białko (na 100 g)',
        'carbs_100g' => 'węglowodany (na 100 g)',
        'fat_100g' => 'tłuszcz (na 100 g)',
        'q' => 'wyszukiwana fraza',
        'per_page' => 'liczba wyników na stronę',
        'grams' => 'gramatura',
        'meal_date' => 'data posiłku',
        'meal_type' => 'rodzaj posiłku',
        'product_id' => 'produkt',
        'product_name' => 'nazwa produktu',
        'quantity' => 'ilość',
        'daily_calorie_goal' => 'dzienny cel kalorii',
    ],
];
