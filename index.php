<?php

$url = $_GET['url'];
$path = $_GET['path'];

$options = [
    'http' => [
        'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
    ],
];
$context = stream_context_create($options);

$html = file_get_contents($url, false, $context);

if ($html === false) {
    echo json_encode(['error' => 'Failed to fetch content.'], JSON_PRETTY_PRINT);
} else {
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $xpathQuery = $path;

    try {
        $elements = $xpath->query($xpathQuery);

        if ($elements === false) {
            throw new Exception("XPath query failed.");
        }

        $result = [];

        foreach ($elements as $element) {
            $elementId = $element->getAttribute('id');

            if (empty($elementId)) {
                $result[0][] = extractElementContent($element);
            } else {

                if (!isset($result[$elementId])) {
                    $result[$elementId] = [];
                }

                $result[$elementId][] = extractElementContent($element);
            }
        }

        $jsonResult = json_encode($result, JSON_PRETTY_PRINT);

        echo $jsonResult;
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
    }
}

function extractElementContent($element)
{
    if ($element->nodeName === 'img') {
        return ['content' => $element->getAttribute('src')];
    } elseif ($element->nodeName === 'button' || $element->nodeName === 'a') {
        $buttonContent = [];
        foreach ($element->childNodes as $childNode) {
            if ($childNode->nodeType === XML_TEXT_NODE && trim($childNode->nodeValue) !== '') {
                $buttonContent[] = ['content' => $childNode->nodeValue];
            } elseif ($childNode->nodeType === XML_ELEMENT_NODE) {
            }
        }
        return $buttonContent;
    } elseif ($element->nodeName === 'iframe') {
        return ['content' => $element->getAttribute('src')];
    } else {
        return ['content' => $element->nodeValue];
    }
}
?>
