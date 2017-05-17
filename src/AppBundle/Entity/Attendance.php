<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Attendance
 *
 * @ORM\Table(name="attendance")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AttendanceRepository")
 */
class Attendance
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \string
     *
     * @ORM\Column(name="arrive", type="string", nullable=true)
     */
    private $arrive;

    /**
     * @var \string
     *
     * @ORM\Column(name="leavee", type="string", nullable=true)
     */
    private $leavee;

    /**
     * @var bool
     *
     * @ORM\Column(name="request_perm", type="boolean", nullable=true)
     */
    private $requestPerm;

    /**
     * @var bool
     *
     * @ORM\Column(name="approved_perm", type="boolean", nullable=true)
     */
    private $approvedPerm;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="attendances")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;
    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer",nullable = true)
     */
    private $userId;

    /**
     * @ORM\ManyToOne(targetEntity="Schedule", inversedBy="attendances")
     * @ORM\JoinColumn(name="schedule_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $schedule;
    /**
     * @var int
     *
     * @ORM\Column(name="schedule_id", type="integer",nullable = true)
     */
    private $scheduleId;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set arrive
     *
     * @param \DateTime $arrive
     *
     * @return Attendance
     */
    public function setArrive($arrive)
    {
        $this->arrive = $arrive;

        return $this;
    }

    /**
     * Get arrive
     *
     * @return \DateTime
     */
    public function getArrive()
    {
        return $this->arrive;
    }

    /**
     * Set leavee
     *
     * @param \DateTime $leavee
     *
     * @return Attendance
     */
    public function setLeavee($leavee)
    {
        $this->leavee = $leavee;

        return $this;
    }

    /**
     * Get leavee
     *
     * @return \DateTime
     */
    public function getLeavee()
    {
        return $this->leavee;
    }

    /**
     * Set requestPerm
     *
     * @param boolean $requestPerm
     *
     * @return Attendance
     */
    public function setRequestPerm($requestPerm)
    {
        $this->requestPerm = $requestPerm;

        return $this;
    }

    /**
     * Get requestPerm
     *
     * @return bool
     */
    public function getRequestPerm()
    {
        return $this->requestPerm;
    }

    /**
     * Set approvedPerm
     *
     * @param boolean $approvedPerm
     *
     * @return Attendance
     */
    public function setApprovedPerm($approvedPerm)
    {
        $this->approvedPerm = $approvedPerm;

        return $this;
    }

    /**
     * Get approvedPerm
     *
     * @return bool
     */
    public function getApprovedPerm()
    {
        return $this->approvedPerm;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Track
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return Track
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }
    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set schedule
     *
     * @param \AppBundle\Entity\Schedule $schedule
     *
     * @return Track
     */
    public function setSchedule(\AppBundle\Entity\Schedule $schedule = null)
    {
        $this->schedule = $schedule;

        return $this;
    }

    /**
     * Get schedule
     *
     * @return \AppBundle\Entity\Schedule
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * Set scheduleId
     *
     * @param integer $scheduleId
     *
     * @return Track
     */
    public function setScheduleId($scheduleId)
    {
        $this->scheduleId = $scheduleId;
        return $this;
    }
    /**
     * Get scheduleId
     *
     * @return integer
     */
    public function getScheduleId()
    {
        return $this->scheduleId;
    }
}
