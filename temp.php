<?php

class node
{
    public $data;
    public $next;

    public function __construct($data)
    {
        $this->data = $data;
        $this->next = null;
    }
}

class singelLinkList
{
    private $list;

    function __construct()
    {
        $this->list = new node('start');
    }

    function add($data)
    {
        $currentNode = $this->list;
        while ($currentNode->next !== null) {
            $currentNode = $currentNode->next;
        }
        $currentNode->next = new node($data);
    }

    function getAllNode()
    {
        return $this->list;
    }

    function reverseList($node)
    {
        if ($node === null || $node->next === null) {
            return $node;
        }
        $res = $this->reverseList($node->next);
        $node->next->next = $node;
        $node->next = null;
        return $res;
    }
}


$list = new singelLinkList();

$list->add(1);
$list->add(2);
$list->add(3);
$list->add(4);
$list->add(5);
$list->add(6);
$list->add(7);
$list->add(8);

$res = $list->reverseList($list->getAllNode());

var_dump($res);




