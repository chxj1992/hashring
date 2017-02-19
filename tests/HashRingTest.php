<?php

namespace Chxj1992\HashRing\Tests;

use Chxj1992\HashRing\HashRing;
use PHPUnit\Framework\TestCase;

class HashRingTest extends TestCase
{

    private function expectNode(HashRing $hashRing, $key, $expectedNode)
    {
        $node = $hashRing->GetNode($key);
        $this->assertEquals($expectedNode, $node);
    }

    private function expectNodes(HashRing $hashRing, $key, array $expectedNodes)
    {
        $nodes = $hashRing->GetNodes($key, 2);
        $this->assertEquals($expectedNodes, $nodes);
    }

    private function expectNodesABC(HashRing $hashRing)
    {
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "b");
        $this->expectNode($hashRing, "test3", "c");
        $this->expectNode($hashRing, "test4", "c");
        $this->expectNode($hashRing, "test5", "a");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "a");
    }

    private function expectNodesABCD(HashRing $hashRing)
    {
        // Somehow adding d does not load balance these keys...
        $this->expectNodesABC($hashRing);
    }

    private function expectNodeRangesABC(HashRing $hashRing)
    {
        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test1", ["b", "c"]);
        $this->expectNodes($hashRing, "test2", ["b", "a"]);
        $this->expectNodes($hashRing, "test3", ["c", "a"]);
        $this->expectNodes($hashRing, "test4", ["c", "b"]);
        $this->expectNodes($hashRing, "test5", ["a", "c"]);
        $this->expectNodes($hashRing, "aaaa", ["b", "a"]);
        $this->expectNodes($hashRing, "bbbb", ["a", "b"]);
    }

    public function testNew()
    {
        $nodes = ["a", "b", "c"];
        $hashRing = new HashRing($nodes);

        $this->expectNodesABC($hashRing);
        $this->expectNodeRangesABC($hashRing);
    }

    public function testNewEmpty()
    {
        $hashRing = new HashRing([]);

        $node = $hashRing->GetNode("test");
        $this->assertEquals("", $node);

        $nodes = $hashRing->GetNodes("test", 2);
        $this->assertEquals([], $nodes);
    }

    public function testForMoreNodes()
    {
        $hashRing = new HashRing(["a", "b", "c"]);

        $nodes = $hashRing->getNodes("'test'", 5);

        $this->assertEquals([], $nodes);
    }


    public function testForEqualNodes()
    {
        $hashRing = new HashRing(["a", "b", "c"]);

        $nodes = $hashRing->getNodes("test", 3);

        $this->assertEquals(["a", "b", "c"], $nodes);
    }


    public function testNewSingle()
    {
        $hashRing = new HashRing(["a"]);

        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test1", "a");
        $this->expectNode($hashRing, "test2", "a");
        $this->expectNode($hashRing, "test3", "a");

        // This triggers the edge case where sortedKey search resulting in not found
        $this->expectNode($hashRing, "test14", "a");

        $this->expectNode($hashRing, "test15", "a");
        $this->expectNode($hashRing, "test16", "a");
        $this->expectNode($hashRing, "test17", "a");
        $this->expectNode($hashRing, "test18", "a");
        $this->expectNode($hashRing, "test19", "a");
        $this->expectNode($hashRing, "test20", "a");
    }


    public function testNewWeighted()
    {
        $hashRing = new HashRing(["a" => 1, "b" => 2, "c" => 1]);

        $this->expectNode($hashRing, "test", "b");
        $this->expectNode($hashRing, "test", "b");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "b");
        $this->expectNode($hashRing, "test3", "c");
        $this->expectNode($hashRing, "test4", "b");
        $this->expectNode($hashRing, "test5", "b");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "a");

        $this->expectNodes($hashRing, "test", ["b", "a"]);
    }

    public function testRemoveNode()
    {
        $hashRing = new HashRing(["a", "b", "c"]);
        $res = $hashRing->removeNode("b");

        $this->assertTrue($res);

        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test1", "c"); // Migrated to c from b
        $this->expectNode($hashRing, "test2", "a"); // Migrated to a from b
        $this->expectNode($hashRing, "test3", "c");
        $this->expectNode($hashRing, "test4", "c");
        $this->expectNode($hashRing, "test5", "a");
        $this->expectNode($hashRing, "aaaa", "a"); // Migrated to a from b
        $this->expectNode($hashRing, "bbbb", "a");

        $this->expectNodes($hashRing, "test", ["a", "c"]);
    }

    public function testAddNode()
    {
        $hashRing = new HashRing(["a", "c"]);
        $hashRing->addNode("b");

        $this->expectNodesABC($hashRing);
    }


    public function testAddNode2()
    {
        $hashRing = new HashRing(["a", "c"]);
        $hashRing->addNode("b");
        $hashRing->addNode("b");

        $this->expectNodesABC($hashRing);
        $this->expectNodeRangesABC($hashRing);
    }

    public function testAddNode3()
    {
        $hashRing = new HashRing(["a", "b", "c"]);
        $hashRing->addNode("d");

        // Somehow adding d does not load balance these keys...
        $this->expectNodesABCD($hashRing);

        $hashRing->addNode("e");

        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "b");
        $this->expectNode($hashRing, "test3", "c");
        $this->expectNode($hashRing, "test4", "c");
        $this->expectNode($hashRing, "test5", "a");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "e"); // Migrated to e from a

        $this->expectNodes($hashRing, "test", ["a", "b"]);
    }

    public function testDuplicateNodes()
    {
        $hashRing = new HashRing(["a", "a", "a", "a", "b"]);

        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "b");
        $this->expectNode($hashRing, "test3", "a");
        $this->expectNode($hashRing, "test4", "b");
        $this->expectNode($hashRing, "test5", "a");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "a");
    }


    public function testAddWeightedNode()
    {
        $hashRing = new HashRing(["a", "c"]);

        $hashRing->addWeightedNode("b", 0);
        $hashRing->addWeightedNode("b", 2);
        $hashRing->addWeightedNode("b", 2);

        $this->expectNode($hashRing, "test", "b");
        $this->expectNode($hashRing, "test", "b");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "b");
        $this->expectNode($hashRing, "test3", "c");
        $this->expectNode($hashRing, "test4", "b");
        $this->expectNode($hashRing, "test5", "b");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "a");

        $this->expectNodes($hashRing, "test", ["b", "a"]);
    }

    public function testUpdateWeightedNod()
    {
        $hashRing = new HashRing(["a", "c"]);
        $hashRing->addWeightedNode("b", 1);
        $hashRing->updateWeightedNode("b", 2);
        $hashRing->updateWeightedNode("b", 2);
        $hashRing->updateWeightedNode("b", 0);
        $hashRing->updateWeightedNode("d", 2);

        $this->expectNode($hashRing, "test", "b");
        $this->expectNode($hashRing, "test", "b");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "b");
        $this->expectNode($hashRing, "test3", "c");
        $this->expectNode($hashRing, "test4", "b");
        $this->expectNode($hashRing, "test5", "b");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "a");

        $this->expectNodes($hashRing, "test", ["b", "a"]);
    }


    public function testRemoveAddNode()
    {
        $hashRing = new HashRing(["a", "b", "c"]);

        $this->expectNodesABC($hashRing);
        $this->expectNodeRangesABC($hashRing);

        $hashRing->removeNode("b");

        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test1", "c"); // Migrated to c from b
        $this->expectNode($hashRing, "test2", "a");  // Migrated to a from b
        $this->expectNode($hashRing, "test3", "c");
        $this->expectNode($hashRing, "test4", "c");
        $this->expectNode($hashRing, "test5", "a");
        $this->expectNode($hashRing, "aaaa", "a");// Migrated to a from b
        $this->expectNode($hashRing, "bbbb", "a");

        $this->expectNodes($hashRing, "test", ["a", "c"]);
        $this->expectNodes($hashRing, "test", ["a", "c"]);
        $this->expectNodes($hashRing, "test1", ["c", "a"]);
        $this->expectNodes($hashRing, "test2", ["a", "c"]);
        $this->expectNodes($hashRing, "test3", ["c", "a"]);
        $this->expectNodes($hashRing, "test4", ["c", "a"]);
        $this->expectNodes($hashRing, "test5", ["a", "c"]);
        $this->expectNodes($hashRing, "aaaa", ["a", "c"]);
        $this->expectNodes($hashRing, "bbbb", ["a", "c"]);

        $hashRing->addNode("b");

        $this->expectNodesABC($hashRing);
        $this->expectNodeRangesABC($hashRing);
    }

    public function testRemoveAddWeightedNode()
    {
        $hashRing = new HashRing(["a" => 1, "b" => 2, "c" => 1]);

        $this->expectNode($hashRing, "test", "b");
        $this->expectNode($hashRing, "test", "b");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "b");
        $this->expectNode($hashRing, "test3", "c");
        $this->expectNode($hashRing, "test4", "b");
        $this->expectNode($hashRing, "test5", "b");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "a");

        $this->expectNodes($hashRing, "test", ["b", "a"]);
        $this->expectNodes($hashRing, "test", ["b", "a"]);
        $this->expectNodes($hashRing, "test1", ["b", "c"]);
        $this->expectNodes($hashRing, "test2", ["b", "a"]);
        $this->expectNodes($hashRing, "test3", ["c", "b"]);
        $this->expectNodes($hashRing, "test4", ["b", "a"]);
        $this->expectNodes($hashRing, "test5", ["b", "a"]);
        $this->expectNodes($hashRing, "aaaa", ["b", "a"]);
        $this->expectNodes($hashRing, "bbbb", ["a", "b"]);

        $hashRing->removeNode("c");

        $this->expectNode($hashRing, "test", "b");
        $this->expectNode($hashRing, "test", "b");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "b");
        $this->expectNode($hashRing, "test3", "b");  // Migrated to b from c
        $this->expectNode($hashRing, "test4", "b");
        $this->expectNode($hashRing, "test5", "b");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "a");

        $this->expectNodes($hashRing, "test", ["b", "a"]);
        $this->expectNodes($hashRing, "test", ["b", "a"]);
        $this->expectNodes($hashRing, "test1", ["b", "a"]);
        $this->expectNodes($hashRing, "test2", ["b", "a"]);
        $this->expectNodes($hashRing, "test3", ["b", "a"]);
        $this->expectNodes($hashRing, "test4", ["b", "a"]);
        $this->expectNodes($hashRing, "test5", ["b", "a"]);
        $this->expectNodes($hashRing, "aaaa", ["b", "a"]);
        $this->expectNodes($hashRing, "bbbb", ["a", "b"]);
    }

    public function testAddRemoveNode()
    {
        $hashRing = new HashRing(["a", "b", "c"]);
        $hashRing->addNode("d");

        $this->expectNodesABCD($hashRing);

        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test1", ["b", "d"]);
        $this->expectNodes($hashRing, "test2", ["b", "d"]);
        $this->expectNodes($hashRing, "test3", ["c", "d"]);
        $this->expectNodes($hashRing, "test4", ["c", "b"]);
        $this->expectNodes($hashRing, "test5", ["a", "d"]);
        $this->expectNodes($hashRing, "aaaa", ["b", "a"]);
        $this->expectNodes($hashRing, "bbbb", ["a", "b"]);

        $hashRing->addNode("e");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "b");
        $this->expectNode($hashRing, "test3", "c");
        $this->expectNode($hashRing, "test4", "c");
        $this->expectNode($hashRing, "test5", "a");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "e");  // Migrated to e from a

        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test1", ["b", "d"]);
        $this->expectNodes($hashRing, "test2", ["b", "d"]);
        $this->expectNodes($hashRing, "test3", ["c", "e"]);
        $this->expectNodes($hashRing, "test4", ["c", "b"]);
        $this->expectNodes($hashRing, "test5", ["a", "e"]);
        $this->expectNodes($hashRing, "aaaa", ["b", "e"]);
        $this->expectNodes($hashRing, "bbbb", ["e", "a"]);

        $hashRing->addNode("f");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "f");
        $this->expectNode($hashRing, "test3", "f");
        $this->expectNode($hashRing, "test4", "c");
        $this->expectNode($hashRing, "test5", "f");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "e");

        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test1", ["b", "d"]);
        $this->expectNodes($hashRing, "test2", ["f", "b"]);
        $this->expectNodes($hashRing, "test3", ["f", "c"]);
        $this->expectNodes($hashRing, "test4", ["c", "b"]);
        $this->expectNodes($hashRing, "test5", ["f", "a"]);
        $this->expectNodes($hashRing, "aaaa", ["b", "e"]);
        $this->expectNodes($hashRing, "bbbb", ["e", "f"]);

        $hashRing->removeNode("e");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test", "a");
        $this->expectNode($hashRing, "test1", "b");
        $this->expectNode($hashRing, "test2", "f");
        $this->expectNode($hashRing, "test3", "f");
        $this->expectNode($hashRing, "test4", "c");
        $this->expectNode($hashRing, "test5", "f");
        $this->expectNode($hashRing, "aaaa", "b");
        $this->expectNode($hashRing, "bbbb", "f"); // Migrated to f from e

        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test1", ["b", "d"]);
        $this->expectNodes($hashRing, "test2", ["f", "b"]);
        $this->expectNodes($hashRing, "test3", ["f", "c"]);
        $this->expectNodes($hashRing, "test4", ["c", "b"]);
        $this->expectNodes($hashRing, "test5", ["f", "a"]);
        $this->expectNodes($hashRing, "aaaa", ["b", "a"]);
        $this->expectNodes($hashRing, "bbbb", ["f", "a"]);

        $hashRing->removeNode("f");
        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test", ["a", "b"]);
        $this->expectNodes($hashRing, "test1", ["b", "d"]);
        $this->expectNodes($hashRing, "test2", ["b", "d"]);
        $this->expectNodes($hashRing, "test3", ["c", "d"]);
        $this->expectNodes($hashRing, "test4", ["c", "b"]);
        $this->expectNodes($hashRing, "test5", ["a", "d"]);
        $this->expectNodes($hashRing, "aaaa", ["b", "a"]);
        $this->expectNodes($hashRing, "bbbb", ["a", "b"]);

        $hashRing->removeNode("d");

        $this->expectNodesABC($hashRing);
        $this->expectNodeRangesABC($hashRing);
    }

}