<?php

require 'Lyfht.php';

$ai = new Lyfth([
    'memory_file' => 'memory.json',
    'context_file' => 'context.json',
    'enable_context' => true,
    'enable_learning' => true,
]);

$ai->loadKnowledge('knowledge.json');
$ai->loadSynonyms('synonyms.json');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
    echo $ai->ask('how are you');  
    ?>
</body>
</html>