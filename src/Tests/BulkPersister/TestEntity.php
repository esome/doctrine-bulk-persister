<?php

namespace esome\Tests\BulkPersister;

use Doctrine\ORM\Mapping as ORM;

/**
 * TestEntity
 *
 * @ORM\Table(name="test_entities")
 * @ORM\Entity()
 */
class TestEntity
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var integer
     **/
    private $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="date", nullable=true)
     */
    private $dateStart;

    /**
     * @ORM\Column(type="string")
     * @var string
     **/
    private $name;

    /**
     * @ORM\Column(name="`like`", type="string")
     * @var string
     **/
    private $like;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $flag;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $flagged;

    /**
     * TestEntity constructor.
     * @param int $id
     * @param \DateTime $dateStart
     * @param string $name
     * @param string $like
     * @param bool $flag
     * @param bool $flagged
     */
    public function __construct($id, $dateStart, $name, $like, $flag, $flagged)
    {
        $this->setId($id);
        $this->setDateStart($dateStart);
        $this->setName($name);
        $this->setLike($like);
        $this->setFlag($flag);
        $this->setFlagged($flagged);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return \DateTime
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * @param \DateTime $dateStart
     */
    public function setDateStart($dateStart)
    {
        $this->dateStart = $dateStart;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLike()
    {
        return $this->like;
    }

    /**
     * @param string $like
     */
    public function setLike($like)
    {
        $this->like = $like;
    }

    /**
     * @return bool
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * @param bool $flag
     */
    public function setFlag($flag)
    {
        $this->flag = $flag;
    }

    /**
     * @return bool
     */
    public function isFlagged()
    {
        return $this->flagged;
    }

    /**
     * @param bool $flagged
     */
    public function setFlagged($flagged)
    {
        $this->flagged = $flagged;
    }

}
