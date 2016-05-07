<?php

use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;

$executionStart = microtime(true);
$memoryStart = memory_get_usage(true);

$em = include 'em.php';

/**
 * initialized in em.php
 *
 * Gedmo\Translatable\TranslationListener
 */
$translatable;

$repository = $em->getRepository('Example\Entity\Category');
$food = $repository->findOneByTitle('Food');
if (!$food) {
    // lets create some categories
    $food = new Example\Entity\Category();
    $food->setTitle('Food');
    $food->addTranslation(new Example\Entity\CategoryTranslation('lt', 'title', 'Maistas'));

    $fruits = new Example\Entity\Category();
    $fruits->setParent($food);
    $fruits->setTitle('Fruits');
    $fruits->addTranslation(new Example\Entity\CategoryTranslation('lt', 'title', 'Vaisiai'));

    $apple = new Example\Entity\Category();
    $apple->setParent($fruits);
    $apple->setTitle('Apple');
    $apple->addTranslation(new Example\Entity\CategoryTranslation('lt', 'title', 'Obuolys'));

    $milk = new Example\Entity\Category();
    $milk->setParent($food);
    $milk->setTitle('Milk');
    $milk->addTranslation(new Example\Entity\CategoryTranslation('lt', 'title', 'Pienas'));

    $em->persist($food);
    $em->persist($milk);
    $em->persist($fruits);
    $em->persist($apple);
    $em->flush();
}

$em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
$baseQuery = $em
    ->createQueryBuilder()
    ->select('node')
    ->from('Example\Entity\Category', 'node')
    ->orderBy('node.root, node.lft', 'ASC')
    ->getQuery()
    ->useResultCache(true);

$query = clone $baseQuery;
$query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'en');
$query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
$query->getResult();

$query = clone $baseQuery;
$query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'lt');
$query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
$query->getResult();

// cache hits follow
var_dump("no on load events");
$query = clone $baseQuery;
$query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'en');
$query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
$query->getResult();

$query = clone $baseQuery;
$query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'lt');
$query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
$query->getResult();

$ms = round(microtime(true) - $executionStart, 4) * 1000;
$mem = round((memory_get_usage(true) - $memoryStart) / 1000000, 2);
echo "Execution took: {$ms} ms, memory consumed: {$mem} Mb";
