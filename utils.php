<?php
function generateDecoratedString($text, $decorationChar = '*', $margin = 2)
{
  // Calculate the total length of the line dynamically
  $sb = preg_match_all('/[\x00-\x7F]/', $text);
  $wb = preg_match_all('/[^\x00-\x7F]/u', $text);

  $lineLength = (int)($wb * 1.7) + $sb + 6 + (2 * $margin);



  // Generate the text line with padding
  $textLine = str_repeat(' ', $margin) . $text . str_repeat(' ', $margin);

  // Generate the top and bottom decoration line
  $decorationLine = str_repeat($decorationChar, $lineLength);

  // Combine everything into the final result without adding extra line breaks
  $result = $decorationLine . PHP_EOL .
    str_repeat($decorationChar, 3) . $textLine . str_repeat($decorationChar, 3) . PHP_EOL .
    $decorationLine;

  return $result;
}

function generateCFPString($records)
{
  $previewData = '';
  foreach ($records as $r) {
    try {
      $type = $r['section_type'];
      $text = $r['section_title'];
      $decorationText = $text;
      if ($type === 'introduction') {
        $decorationText = '=';
      } else if ($type === 'important') {
        $decorationText = '*';
      } else if ($type === 'deadline') {
        $decorationText = '-';
      } else if ($type === 'contact') {
        $decorationText = '+';
      } else {
        $decorationText = '*';
      }
      $decoratedString = generateDecoratedString("{$text}", $decorationText, 1);
      $previewData .= $decoratedString . "\n\n";
      $previewData .= strip_tags($r['section_body'] ?? '') . "\n\n";
    } catch (Exception $e) {
      echo "Error: " . $e->getMessage();
    }
  }
  return $previewData;
}

/**
 * レコードをHTMLマークアップで出力する
 * section_title はエスケープ、section_body は生のHTMLとして出力
 */
function generateCFPHtml(array $records): string
{
  $html = '';
  foreach ($records as $r) {
    $type  = htmlspecialchars($r['section_type'], ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($r['section_title'], ENT_QUOTES, 'UTF-8');
    // エンティティ化されたタグをデコードして生HTMLとして扱う
    $body  = html_entity_decode($r['section_body'] ?? '', ENT_QUOTES, 'UTF-8');
    // $sectionId = 'cfp-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    $sectionId = 'cfp-' . $title;
    $ariaLabel = $title;

    $html .= "<section id=\"{$sectionId}\" class=\"cfp-section cfp-{$type}\" aria-labelledby=\"heading-{$sectionId}\">";
    $html .= "<h2 id=\"heading-{$sectionId}\" class=\"mt-4\" tabindex=\"-1\">{$title}</h2>";
    $html .= "<div role=\"region\" aria-label=\"{$ariaLabel}\" class=\"mt-3\">{$body}</div>";
    $html .= "</section>\n";
  }
  return $html;
}

function autoLink($text)
{
  // まずはテキスト全体をエスケープ
  $escapedText = htmlspecialchars($text, ENT_QUOTES);

  // 正規表現で「https://」で始まるURLを検出
  $pattern = '/(https:\/\/[^\s]+)/';

  // マッチしたURL部分をアンカータグに置換
  $linkedText = preg_replace_callback($pattern, function ($matches) {
    $url = $matches[0];
    return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>';
  }, $escapedText);

  return $linkedText;
}
