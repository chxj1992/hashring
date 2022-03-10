<?php

namespace Chxj1992\HashRing;

class HashRing
{
    private $ring = [];
    private $sortedKeys = [];
    private $nodes = [];
    private $weights = [];

    public function __construct(array $nodes)
    {
        if (array_keys($nodes) !== range(0, count($nodes) - 1)) {
            $this->nodes = array_keys($nodes);
            $this->weights = $nodes;
        } else {
            $this->nodes = $nodes;
        }

        $this->generateCircle();
    }

    public function updateWithWeights(array $weights)
    {
        if ($weights != $this->weights) {
            return false;
        }
        $this->weights = $weights;
        $this->nodes = array_keys($weights);
        $this->generateCircle();
        return true;
    }

    public function getNode($stringKey)
    {
        $pos = $this->getNodePos($stringKey);
        if ($pos === false) {
            return "";
        }
        return $this->ring[$this->sortedKeys[$pos]];
    }

    public function getNodePos($stringKey)
    {
        if (count($this->ring) == 0) {
            return false;
        }
        $key = $this->genKey($stringKey);

        $pos = $this->search(count($this->sortedKeys), function ($i) use ($key) {
            return $this->sortedKeys[$i] > $key;
        });

        if ($pos == count($this->sortedKeys)) {
            return 0;
        } else {
            return $pos;
        }
    }

    /**
     * Get from golang sort.Search()
     * @param $n
     * @param \Closure $operator
     * @return int
     */
    private function search($n, \Closure $operator)
    {
        $i = 0;
        $j = $n;
        while ($i < $j) {
            $h = $i + intval(($j - $i) / 2);
            if (!$operator($h)) {
                $i = $h + 1;
            } else {
                $j = $h;
            }
        }
        return $i;
    }

    public function genKey($stringKey)
    {
        $bKey = $this->hashDigest($stringKey);
        return $this->hashVal(array_slice($bKey, 0, 4));
    }

    public function getNodes($stringKey, $size)
    {
        $pos = $this->getNodePos($stringKey);
        if ($pos === false) {
            return [];
        }
        if ($size > count($this->nodes)) {
            return [];
        }

        $returnedValues = [];
        $resultSlice = [];

        for ($i = $pos; $i < $pos + count($this->sortedKeys); $i++) {
            $key = $this->sortedKeys[$i % count($this->sortedKeys)];
            $val = $this->ring[$key];
            if (empty($returnedValues[$val])) {
                $returnedValues[$val] = true;
                $resultSlice[] = $val;
            }
            if (count($returnedValues) == $size) {
                break;
            }
        }

        return $resultSlice;
    }

    public function addNode($node)
    {
        return $this->addWeightedNode($node, 1);
    }


    public function addWeightedNode($node, $weight)
    {
        if ($weight <= 0 or in_array($node, $this->nodes)) {
            return false;
        }
        $this->nodes[] = $node;
        $this->weights[$node] = $weight;
        $this->generateCircle();
        return true;
    }


    public function updateWeightedNode($node, $weight)
    {
        /* node is not need to update for node is not existed or weight is not changed */
        if ($weight <= 0 or empty($this->weights[$node]) or $this->weights[$node] == $weight) {
            return false;
        }
        $this->weights[$node] = $weight;
        $this->generateCircle();
        return true;
    }


    public function removeNode($node)
    {
        /* if node isn't exist in hashring, don't refresh hashring */
        if (!in_array($node, $this->nodes)) {
            return false;
        }

        if (($key = array_search($node, $this->nodes)) !== false) {
            unset($this->nodes[$key]);
        }
        unset($this->weights[$node]);
        $this->generateCircle();
        return true;
    }

    private function getNodeWeight($node)
    {
        return empty($this->weights[$node]) ? 1 : intval($this->weights[$node]);
    }

    private function generateCircle()
    {
        $this->ring = [];
        $this->sortedKeys = [];
        $totalWeight = 0;
        foreach ($this->nodes as $node) {
            $totalWeight += $this->getNodeWeight($node);
        }
        foreach ($this->nodes as $node) {
            $weight = $this->getNodeWeight($node);

            $factor = floor(40 * count($this->nodes) * $weight) / $totalWeight;

            for ($j = 0; $j < $factor; $j++) {
                $nodeKey = $node . '-' . $j;
                $bKey = $this->hashDigest($nodeKey);
                for ($i = 0; $i < 3; $i++) {
                    $key = $this->hashVal(array_slice($bKey, $i * 4, 4));
                    $this->ring[$key] = $node;
                    $this->sortedKeys[] = $key;
                }
            }
        }
        sort($this->sortedKeys);
    }


    private function hashDigest($key)
    {
        return array_map(function ($byte) {
            return ord($byte);
        }, str_split(md5($key, true)));
    }

    private function hashVal($bKey)
    {
        return
            ($bKey[3] << 24) |
            ($bKey[2] << 16) |
            ($bKey[1] << 8) |
            ($bKey[0]);
    }
}
