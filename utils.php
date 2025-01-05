<?php
function generateDecoratedString($text, $decorationChar = '*', $margin = 2) {
    // Calculate the total length of the line dynamically
    $lineLength = strlen($text) + 6 + (2 * $margin); // 6 for *** and 2 margins on both sides
  
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
  
  function generateCFPString($records) {
    $previewData = '';
    foreach ($records as $r) {
      try {                    
        $type = $r['section_type'];
        $text = $r['section_title'];
        $decorationText = $text;
        if( $type === 'introduction' ){
          $decorationText = '=';
        } else if( $type === 'important' ){
          $decorationText = '*';
        } else if( $type === 'deadline' ){
          $decorationText = '-';
        } else if( $type === 'contact' ){
          $decorationText = '+';
        }
        else {
          $decorationText = '*';
        }
        $decoratedString = generateDecoratedString("{$text}", $decorationText, 1);
        $previewData .= $decoratedString . "\n\n";
        $previewData .= ($r['section_body'] ?? '') . "\n\n";
        
       } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
      }
      
    }
    return $previewData;    
  }
  
?>