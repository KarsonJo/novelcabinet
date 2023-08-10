<?php

namespace NovelCabinet\User {
    const GENDERS = ['male' => 'male', 'female' => 'female'];

    function validate_gender($gender)
    {
        return isset(GENDERS[$gender]);
    }
}
