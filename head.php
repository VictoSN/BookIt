<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="icon" type="image/png" href="public/logo-circle.png"/>
        <title><?php echo $page_title . " - BookIt" ?? "bookIt"; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen flex flex-col">
        <header class="flex flex-row justify-between items-center p-2 px-8 border-b border-black">
            <p class="font-bold text-3xl">BookIt</p>
            <a href="https://github.com/VictoSN" target="_blank" rel="noopener noreferrer">
                <img class="max-w-[40px] rounded-full" src="public/logo-circle.png">
            </a>
        </header>

        <div class="flex flex-1">
            <?php include "nav.php"; ?>