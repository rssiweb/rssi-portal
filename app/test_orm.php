<?php

require_once "bootstrap.php";

// $memberRepository = $entityManager->getRepository('Member');
// $members = $memberRepository->findAll();

// foreach ($members as $member) {
//     echo sprintf("-%s\n", $member->getFullName());
// }

$pregResult = preg_match('/' . '^rssimyaccount_members$' . '/', "rssimyaccount_members");
echo $pregResult
