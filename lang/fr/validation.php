<?php

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'array' => 'Le champ :attribute doit être une liste.',
    'between' => [
        'array' => 'Le champ :attribute doit contenir entre :min et :max éléments.',
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'string' => 'Le champ :attribute doit contenir entre :min et :max caractères.',
    ],
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'date' => 'Le champ :attribute doit être une date valide.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'enum' => 'La valeur du champ :attribute n\'est pas valide.',
    'exists' => 'La valeur du champ :attribute n\'existe pas.',
    'gt' => [
        'numeric' => 'Le champ :attribute doit être supérieur à :value.',
    ],
    'in' => 'La valeur du champ :attribute n\'est pas valide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'lowercase' => 'Le champ :attribute doit être en minuscules.',
    'max' => [
        'array' => 'Le champ :attribute ne peut pas contenir plus de :max éléments.',
        'numeric' => 'Le champ :attribute ne peut pas dépasser :max.',
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'min' => [
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'numeric' => 'Le champ :attribute doit être au moins égal à :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'password' => [
        'letters' => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le champ :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le champ :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Ce :attribute est apparu dans une fuite de données. Choisis-en un autre.',
    ],
    'regex' => 'Le format du champ :attribute n\'est pas valide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'size' => [
        'string' => 'Le champ :attribute doit contenir :size caractères.',
    ],
    'string' => 'Le champ :attribute doit être un texte.',
    'unique' => 'Ce :attribute est déjà utilisé.',
    'uuid' => 'Le champ :attribute doit être un UUID valide.',

    'attributes' => [
        'name' => 'nom',
        'email' => 'e-mail',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'current_password' => 'mot de passe actuel',
        'device_name' => 'nom de l\'appareil',
        'code' => 'code',
    ],
];
