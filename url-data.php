<?php

header('Content-Type: application/json');

// get url from request
$url = $_GET['url'];

if ($url) {
  // get data from url
  $data = fetchData($url);

  // return data as json
  echo json_encode([
    'success' => 1,
    'meta' => $data,
  ]);
} else {
  // return error if url is missing
  echo json_encode(['error' => 'URL is missing']);
}

/**
 * Fetches and parses HTML content from a given URL to extract metadata.
 * This function is used to show the preview of the page on the client (links in the post content). 
 *
 * This function retrieves the HTML content of the specified URL and extracts 
 * the `<title>`, meta description, and Open Graph image URL, if available.
 * It uses regular expressions to find these elements within the HTML content.
 *
 * @param string $url The URL from which to fetch the HTML content.
 * @return array An associative array containing the extracted 'title', 'description',
 *               and 'image' metadata. The 'image' key contains a nested array with 'url'.
 */

function fetchData($url) {

  $response = file_get_contents($url);

  $title = '';
  $description = '';
  $image = '';
  if (!empty($response)) {
    // use regular expressions to find the title, description, and image
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
