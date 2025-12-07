<?php
include 'admin/koneksi.php';

function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return empty($text) ? 'artikel-' . time() : $text;
}

$result = mysqli_query($conn, "SELECT id, title FROM articles WHERE slug IS NULL OR slug = ''");

while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['id'];
    $title = $row['title'];
    $slug = slugify($title);

 
    $original_slug = $slug;
    $i = 1;
    while (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM articles WHERE slug = '$slug' AND id != $id")) > 0) {
        $slug = $original_slug . '-' . $i++;
    }

   
    mysqli_query($conn, "UPDATE articles SET slug = '$slug' WHERE id = $id");

    echo "ID $id → Slug: $slug <br>";
}

echo "<hr>Selesai update slug!";
?>
