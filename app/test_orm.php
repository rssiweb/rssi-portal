<?php
// move it inside html to make it work
require_once __DIR__ . '/../bootstrap.php';

$memberRepository = $entityManager->getRepository('RssimyaccountMembers');
$members = $memberRepository->findAll();

foreach ($members as $member) {
    echo sprintf("-%s\n", $member->getAssociatenumber());
}
