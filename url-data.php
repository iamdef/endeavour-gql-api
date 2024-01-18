<?php

header('Content-Type: application/json');

// Получаем URL из запроса
$url = $_GET['url'];

if ($url) {
  // Выполняем запрос к URL для получения данных
  $data = fetchData($url);

  // Возвращаем данные в формате JSON
  echo json_encode([
    'success' => 1,
    'meta' => $data,
  ]);
} else {
  // Если URL не был предоставлен, возвращаем ошибку
  echo json_encode(['error' => 'URL is missing']);
}

function fetchData($url) {
  // Здесь вы можете использовать любой метод для извлечения данных по URL
  // Например, с использованием cURL
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  curl_close($ch);

  // Здесь можно распарсить $response и извлечь нужные данные (заголовок, описание и т. д.)
  // В этом примере, возвращаем простой ассоциативный массив
  return [
    'title' => 'CodeX Team',
    'description' => 'Club of web-development, design and marketing. We build team learning how to build full-valued projects on the world market.',
    'image' => [
      'url' => 'https://codex.so/public/app/img/meta_img.png',
    'res' => $response  
    ],
  ];
}
