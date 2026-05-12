<?php

enum Page
{
    case Home;
    case Contact;
    case Logout;

    public function text(): string
    {
        return match($this) {
            Page::Home => 'Accueil',
            Page::Contact => 'Contact',
            Page::Logout => 'DÃ©connexion',
        };
    }

    public function url(): string
    {
        return match($this) {
            Page::Home => '/',
            Page::Contact => '/contact.php',
            Page::Logout => '/logout.php',
        };
    }
}

