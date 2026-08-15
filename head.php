<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <link rel="icon" type="image/png" href="public/logo-circle.png"/>
        <title><?php echo $page_title . " - BookIt" ?? "bookIt"; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen flex flex-col bg-[#3a2b98] text-[#dddddd]">
        <header class="flex flex-row justify-between items-center p-2 px-4 bg-[#dddddd] text-[#3a2b98]">
            <p class="font-bold text-3xl">BookIt</p>
            <div class="flex flex-row gap-2 items-center">
                <p>Follow me!</p>
                <a href="https://github.com/VictoSN" target="_blank" rel="noopener noreferrer">
                    <img class="max-w-[40px] rounded-full" src="public/logo-circle.png">
                </a>
            </div>
        </header>

        <div class="flex flex-1 p-2 px-4 gap-12">
            <?php include "nav.php"; ?>