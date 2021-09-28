<?php
/*
 * Copyright 2019-2021 Pepper, Inc.
 *
 * Licensed under the MIT (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   https://opensource.org/licenses/mit-license.php
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace pepper;

use Exception;

/**
 * Class SnowFlake
 * @package app\extend
 */
class SnowFlake
{
    /**
     * 时间起始标记点，作为基准，一般取系统的最近时间（一旦确定不能变动）
     */
    const EPOCH = 1288834974657;

    /**
     * 机器标识位数
     */
    const WORKER_ID_BITS = 5;

    /**
     * 数据中心标识位数
     */
    const DC_ID_BITS = 5;

    /**
     * 毫秒内自增位
     */
    const SEQUENCE_BITS = 12;

    /**
     * @var int 工作机器ID
     */
    private $workerId;

    /**
     * @var int 数据中心ID
     */
    private $dcId;

    /**
     * @var int 毫秒内序列
     */
    private $sequence;

    /**
     * @var int 机器ID最大值
     */
    private $maxWorkerId = -1 ^ (-1 << self::WORKER_ID_BITS);

    /**
     * @var int 数据中心ID最大值
     */
    private $maxDcId = -1 ^ (-1 << self::DC_ID_BITS);

    /**
     * @var int 机器ID偏左移位数
     */
    private $workerIdShift = self::SEQUENCE_BITS;

    /**
     * @var int 数据中心ID左移位数
     */
    private $dcIdShift  = self::SEQUENCE_BITS + self::WORKER_ID_BITS;

    /**
     * @var int 时间毫秒左移位数
     */
    private $timestampLeftShift = self::SEQUENCE_BITS + self::WORKER_ID_BITS + self::DC_ID_BITS;

    /**
     * @var int 生成序列的掩码
     */
    private $sequenceMask = -1 ^ (-1 << self::SEQUENCE_BITS);

    /**
     * @var int 上次生产ID时间戳
     */
    private $lastTimestamp = -1;

    /**
     * SnowFlakeDemo2 constructor.
     * @param int $workerId
     * @param int $dcId
     * @param int $sequence
     * @throws \Exception
     */
    public function __construct($workerId, $dcId, $sequence = 0)
    {
        if ($workerId > $this->maxWorkerId || $workerId < 0) {
            throw new Exception("worker Id can't be greater than {$this->maxWorkerId} or less than 0");
        }

        if ($dcId > $this->maxDcId || $dcId < 0) {
            throw new Exception("data center Id can't be greater than {$this->maxDcId} or less than 0");
        }

        $this->workerId = $workerId;
        $this->dcId = $dcId;
        $this->sequence = $sequence;
    }

    /**
     * 获取动态ID
     *
     * @return int
     * @throws Exception
     */
    public function getId()
    {
        $timestamp = $this->timeGen();

        if ($timestamp < $this->lastTimestamp) {
            $diffTimestamp = bcsub($this->lastTimestamp, $timestamp);
            throw new Exception("Clock moved backwards.  Refusing to generate id for {$diffTimestamp} milliseconds");
        }

        if ($this->lastTimestamp == $timestamp) {
            $this->sequence = ($this->sequence + 1) & $this->sequenceMask;
            if (0 == $this->sequence) {
                $timestamp = $this->tilNextMillis($this->lastTimestamp);
            }
        } else {
            $this->sequence = 0;
        }

        $this->lastTimestamp = $timestamp;

        return (($timestamp - self::EPOCH) << $this->timestampLeftShift) |
            ($this->dcId << $this->dcIdShift) |
            ($this->workerId << $this->workerIdShift) |
            $this->sequence;
    }

    /**
     * 获取下次时间戳
     *
     * @param int $lastTimestamp
     * @return false|float
     */
    protected function tilNextMillis($lastTimestamp)
    {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }

        return $timestamp;
    }

    /**
     * 时钟生成器
     *
     * @return false|float
     */
    protected function timeGen()
    {
        return floor(microtime(true) * 1000);
    }


}