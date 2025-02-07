<?php
//created by CHATGPT
use SebastianBergmann\Diff\Differ;

class StringUtils {
    public static function compare_and_replace($string1, $string2) {
        $differ = new Differ();
        $diff = $differ->diffToArray($string1, $string2);

        $commonParts = [];
        $differentParts = [];

        foreach ($diff as $part) {
            if ($part[1] === 0) {
                // Unchanged part
                $commonParts[] = $part[0];
            } else {
                // Changed part
                $differentParts[] = $part[0];
            }
        }

        // Fetch all placeholders from the database
        $placeholders = Capsule::table('placeholders')->get();

        // Replace common parts with placeholders
        foreach ($commonParts as $commonPart) {
            $placeholder = self::get_or_create_placeholder($commonPart, $placeholders);
            $string1 = str_replace($commonPart, $placeholder, $string1);
            $string2 = str_replace($commonPart, $placeholder, $string2);
        }

        return [
            'string1' => $string1,
            'string2' => $string2,
            'common' => $commonParts,
            'different' => $differentParts,
        ];
    }

    private static function get_or_create_placeholder($originalString, &$placeholders) {
        // Check if the placeholder already exists
        foreach ($placeholders as $placeholder) {
            if ($placeholder->original_string === $originalString) {
                return $placeholder->placeholder;
            }
        }

        // Create a new placeholder
        $newPlaceholder = '{' . count($placeholders) . '}';
        Capsule::table('placeholders')->insert([
            'placeholder' => $newPlaceholder,
            'original_string' => $originalString
        ]);

        // Update the placeholders array
        $placeholders[] = (object) ['placeholder' => $newPlaceholder, 'original_string' => $originalString];

        return $newPlaceholder;
    }
}

// Initialize the database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => 'path/to/your/database.sqlite',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Create the table if it does not exist
if (!Capsule::schema()->hasTable('placeholders')) {
    Capsule::schema()->create('placeholders', function ($table) {
        $table->increments('id');
        $table->string('placeholder');
        $table->string('original_string');
    });
}

// Example usage
$string1 = "The quick brown fox jumps over the lazy dog.";
$string2 = "The quick brown cat jumps over the lazy dog.";

$result = StringUtils::compare_and_replace($string1, $string2);

echo "String 1 with placeholders:\n";
echo $result['string1'] . "\n";

echo "String 2 with placeholders:\n";
echo $result['string2'] . "\n";

echo "Common parts:\n";
print_r($result['common']);

echo "\nDifferent parts:\n";
print_r($result['different']);
