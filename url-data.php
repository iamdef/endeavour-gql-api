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

  $response = file_get_contents($url);

  $title = '';
  $description = '';
  $image = '';
  if (!empty($response)) {
    // Используем регулярное выражение для поиска тега <title> и его содержимого
    if (preg_match("/<title>(.*?)<\/title>/i", $response, $matches)) {
        $title = $matches[1];
    }
    if (preg_match('/<meta[^>]+name="description"[^>]+content="([^"]+)"[^>]*>/i', $response, $matches)) {
      $description = $matches[1];
    }
    if (preg_match('/<meta[^>]+property="og:image"[^>]+content="([^"]+)"[^>]*>/i', $response, $matches)) {
      $image = $matches[1];
    }
}

  return [
    'title' => $title ,
    'description' => $description,
    'image' => [
      'url' => $image,
    ]
  ];
}
