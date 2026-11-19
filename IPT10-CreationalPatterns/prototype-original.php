<?php

declare(strict_types=1);

interface CallingCardPrototype
{
    public function setStudent(array $student): void;

    public function render(): void;

    public function save(string $filename): bool;

    public function destroy(): void;
}

final class CallingCard implements CallingCardPrototype
{
    private const WIDTH = 1000;
    private const HEIGHT = 600;

    private $image;
    private array $colors = [];
    private array $student = [];
    private string $businessName = 'College of Computing Studies';
    private string $position = 'BSIT Student';
    private string $street = 'AUF CCS Building';
    private string $city = 'Angeles City';

    public function __construct()
    {
        $this->createCanvas();
    }

    public function __destruct()
    {
        $this->destroy();
    }

    public function __clone()
    {
        $this->createCanvas();
    }

    public function setStudent(array $student): void
    {
        if (!isset($student['first_name'], $student['last_name'])) {
            throw new InvalidArgumentException('Each student must have first_name and last_name.');
        }

        $this->student = $student;
    }

    public function setBusinessName(string $businessName): void
    {
        $this->businessName = $businessName;
    }

    public function setPosition(string $position): void
    {
        $this->position = $position;
    }

    public function setAddress(string $street, string $city): void
    {
        $this->street = $street;
        $this->city = $city;
    }

    public function render(): void
    {
        if ($this->student === []) {
            throw new RuntimeException('A student must be assigned before rendering.');
        }

        imagefill($this->image, 0, 0, $this->colors['background']);

        imagefilledrectangle(
            $this->image,
            50,
            50,
            self::WIDTH - 50,
            self::HEIGHT - 50,
            $this->colors['white']
        );

        imagefilledrectangle(
            $this->image,
            50,
            50,
            75,
            self::HEIGHT - 50,
            $this->colors['blue']
        );

        $firstName = (string) $this->student['first_name'];
        $lastName = (string) $this->student['last_name'];
        $name = trim($firstName . ' ' . $lastName);
        $email = strtolower($lastName . '.' . $firstName . '@auf.edu.ph');
        $phone = sprintf(
            '+1 (555) %03d-%04d',
            random_int(100, 999),
            random_int(1000, 9999)
        );
        $address = $this->street . ', ' . $this->city;

        imagestring(
            $this->image,
            5,
            120,
            100,
            strtoupper($this->businessName),
            $this->colors['blue']
        );

        imagestring(
            $this->image,
            5,
            120,
            170,
            $name,
            $this->colors['black']
        );

        imagestring(
            $this->image,
            4,
            120,
            210,
            $this->position,
            $this->colors['blue']
        );

        imageline(
            $this->image,
            120,
            260,
            self::WIDTH - 120,
            260,
            $this->colors['gray']
        );

        imagestring(
            $this->image,
            4,
            120,
            310,
            'Email: ' . $email,
            $this->colors['black']
        );

        imagestring(
            $this->image,
            4,
            120,
            365,
            'Phone: ' . $phone,
            $this->colors['black']
        );

        imagestring(
            $this->image,
            4,
            120,
            420,
            'Address: ' . $address,
            $this->colors['black']
        );

        imagestring(
            $this->image,
            3,
            120,
            485,
            'www.auf.edu.ph',
            $this->colors['gray']
        );
    }

    public function save(string $filename): bool
    {
        return imagepng($this->image, $filename);
    }

    public function destroy(): void
    {
        if ($this->image !== null) {
            imagedestroy($this->image);
            $this->image = null;
        }
    }

    private function createCanvas(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('The PHP GD extension is required.');
        }

        $this->destroy();

        $this->image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $this->colors = [
            'background' => imagecolorallocate($this->image, 245, 247, 250),
            'white' => imagecolorallocate($this->image, 255, 255, 255),
            'black' => imagecolorallocate($this->image, 30, 30, 30),
            'gray' => imagecolorallocate($this->image, 100, 100, 100),
            'blue' => imagecolorallocate($this->image, 40, 100, 200),
        ];
    }
}

$students = [
    [
        'first_name' => 'Felicity',
        'last_name' => 'Hampton',
    ],
    [
        'first_name' => 'Hank',
        'last_name' => 'Rice',
    ],
    [
        'first_name' => 'Ada',
        'last_name' => 'Wilson',
    ],
    [
        'first_name' => 'Daniel',
        'last_name' => 'Salgado',
    ],
    [
        'first_name' => 'Avalynn',
        'last_name' => 'Crane',
    ],
    [
        'first_name' => 'Fox',
        'last_name' => 'Summers',
    ],
    [
        'first_name' => 'Frankie',
        'last_name' => 'Andersen',
    ],
    [
        'first_name' => 'Alistair',
        'last_name' => 'Decker',
    ],
    [
        'first_name' => 'Aleena',
        'last_name' => 'Phillips',
    ],
    [
        'first_name' => 'Andrew',
        'last_name' => 'Marks',
    ],
    [
        'first_name' => 'Monica',
        'last_name' => 'French',
    ],
    [
        'first_name' => 'Corey',
        'last_name' => 'Hess',
    ],
    [
        'first_name' => 'Kaliyah',
        'last_name' => 'Richard',
    ],
    [
        'first_name' => 'Ahmed',
        'last_name' => 'Richardson',
    ],
    [
        'first_name' => 'Allison',
        'last_name' => 'Cortes',
    ],
    [
        'first_name' => 'Banks',
        'last_name' => 'McGee',
    ],
    [
        'first_name' => 'Kayleigh',
        'last_name' => 'Mendoza',
    ],
    [
        'first_name' => 'Dominic',
        'last_name' => 'Atkins',
    ],
    [
        'first_name' => 'Mina',
        'last_name' => 'Beasley',
    ],
    [
        'first_name' => 'Stanley',
        'last_name' => 'Jefferson',
    ],
];

$outputDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'cards';

if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0755, true) && !is_dir($outputDirectory)) {
    throw new RuntimeException('Could not create the cards directory.');
}

$prototype = new CallingCard();

foreach ($students as $student) {
    $card = clone $prototype;
    $card->setStudent($student);

    $card->render();

    $filename = $outputDirectory . DIRECTORY_SEPARATOR . 'calling-card-' .
        strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($student['first_name'] . '-' . $student['last_name']))) .
        '.png';

    if (!$card->save($filename)) {
        throw new RuntimeException('Could not save calling card for ' . $student['first_name'] . ' ' . $student['last_name'] . '.');
    }

    $card->destroy();
    echo 'Generated: ' . basename($filename) . PHP_EOL;
}

$prototype->destroy();

echo 'Generated ' . count($students) . ' calling cards.' . PHP_EOL;
