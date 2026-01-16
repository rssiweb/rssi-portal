<?php
session_start();

function generateMathCaptcha() {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $operators = ['+', '-', '*'];
    $operator = $operators[array_rand($operators)];
    
    switch($operator) {
        case '+': $answer = $num1 + $num2; break;
        case '-': $answer = $num1 - $num2; break;
        case '*': $answer = $num1 * $num2; break;
    }
    
    $_SESSION['captcha_answer'] = $answer;
    $_SESSION['captcha_question'] = "$num1 $operator $num2";
    
    return $_SESSION['captcha_question'];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'question' => generateMathCaptcha()
]);
?>