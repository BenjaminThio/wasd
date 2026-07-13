<?php
    enum CategoryColor
    {
        case Magenta;
        case Yellow;
        case Green;
        case Blue;
        case Purple;
    }

    class Category
    {
        private string $name;
        private CategoryColor $color;

        public function __construct($name, $color)
        {
            $this->name = $name;
            $this->color = $color;
        }

        public function getName(): string
        {
            return $this->name;
        }

        public function getColor(): CategoryColor
        {
            return $this->color;
        }
    }
?>