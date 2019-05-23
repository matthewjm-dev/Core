<?php // Twig Helper

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class twig_helper
{
    protected $loader;
    protected $twig;

    public function __construct()
    {
        $this->loader = new FilesystemLoader(ipsCore::$path_app . '/views/');
        $this->twig = new Environment($this->loader);
    }

    public function render($template, array $data = [])
    {
        echo $this->twig->render($template . '.twig', $data);
    }
}
