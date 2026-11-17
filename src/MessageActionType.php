<?php

namespace Dvarilek\FilamentConverse;

enum MessageActionType: string
{
    case REPLY = 'reply';

    case EDIT = 'edit';
}
