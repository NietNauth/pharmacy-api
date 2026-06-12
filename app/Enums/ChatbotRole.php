<?php

namespace App\Enums;

enum ChatbotRole: string
{
    case USER = 'user';
    case ASSISTANT = 'assistant';
    case SYSTEM = 'system';
}
