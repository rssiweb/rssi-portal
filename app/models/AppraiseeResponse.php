<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * AppraiseeResponse
 *
 * @ORM\Table(name="appraisee_response")
 * @ORM\Entity
 */
class AppraiseeResponse
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="appraisee_response_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="goalsheetid", type="text", nullable=true)
     */
    private $goalsheetid;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_associatenumber", type="text", nullable=true)
     */
    private $appraiseeAssociatenumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_associatenumber", type="text", nullable=true)
     */
    private $managerAssociatenumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewer_associatenumber", type="text", nullable=true)
     */
    private $reviewerAssociatenumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="role", type="text", nullable=true)
     */
    private $role;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisaltype", type="text", nullable=true)
     */
    private $appraisaltype;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisalyear", type="text", nullable=true)
     */
    private $appraisalyear;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_1", type="text", nullable=true)
     */
    private $parameter1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_1", type="text", nullable=true)
     */
    private $expectation1;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_1", type="integer", nullable=true)
     */
    private $maxRating1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_2", type="text", nullable=true)
     */
    private $parameter2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_2", type="text", nullable=true)
     */
    private $expectation2;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_2", type="integer", nullable=true)
     */
    private $maxRating2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_3", type="text", nullable=true)
     */
    private $parameter3;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_3", type="text", nullable=true)
     */
    private $expectation3;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_3", type="integer", nullable=true)
     */
    private $maxRating3;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_4", type="text", nullable=true)
     */
    private $parameter4;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_4", type="text", nullable=true)
     */
    private $expectation4;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_4", type="integer", nullable=true)
     */
    private $maxRating4;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_5", type="text", nullable=true)
     */
    private $parameter5;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_5", type="text", nullable=true)
     */
    private $expectation5;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_5", type="integer", nullable=true)
     */
    private $maxRating5;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_6", type="text", nullable=true)
     */
    private $parameter6;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_6", type="text", nullable=true)
     */
    private $expectation6;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_6", type="integer", nullable=true)
     */
    private $maxRating6;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_7", type="text", nullable=true)
     */
    private $parameter7;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_7", type="text", nullable=true)
     */
    private $expectation7;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_7", type="integer", nullable=true)
     */
    private $maxRating7;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_8", type="text", nullable=true)
     */
    private $parameter8;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_8", type="text", nullable=true)
     */
    private $expectation8;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_8", type="integer", nullable=true)
     */
    private $maxRating8;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_9", type="text", nullable=true)
     */
    private $parameter9;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_9", type="text", nullable=true)
     */
    private $expectation9;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_9", type="integer", nullable=true)
     */
    private $maxRating9;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_10", type="text", nullable=true)
     */
    private $parameter10;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_10", type="text", nullable=true)
     */
    private $expectation10;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_10", type="integer", nullable=true)
     */
    private $maxRating10;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_11", type="text", nullable=true)
     */
    private $parameter11;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_11", type="text", nullable=true)
     */
    private $expectation11;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_11", type="integer", nullable=true)
     */
    private $maxRating11;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_12", type="text", nullable=true)
     */
    private $parameter12;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_12", type="text", nullable=true)
     */
    private $expectation12;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_12", type="integer", nullable=true)
     */
    private $maxRating12;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_13", type="text", nullable=true)
     */
    private $parameter13;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_13", type="text", nullable=true)
     */
    private $expectation13;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_13", type="integer", nullable=true)
     */
    private $maxRating13;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_14", type="text", nullable=true)
     */
    private $parameter14;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_14", type="text", nullable=true)
     */
    private $expectation14;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_14", type="integer", nullable=true)
     */
    private $maxRating14;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_15", type="text", nullable=true)
     */
    private $parameter15;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_15", type="text", nullable=true)
     */
    private $expectation15;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_15", type="integer", nullable=true)
     */
    private $maxRating15;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_16", type="text", nullable=true)
     */
    private $parameter16;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_16", type="text", nullable=true)
     */
    private $expectation16;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_16", type="integer", nullable=true)
     */
    private $maxRating16;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_17", type="text", nullable=true)
     */
    private $parameter17;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_17", type="text", nullable=true)
     */
    private $expectation17;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_17", type="integer", nullable=true)
     */
    private $maxRating17;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_18", type="text", nullable=true)
     */
    private $parameter18;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_18", type="text", nullable=true)
     */
    private $expectation18;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_18", type="integer", nullable=true)
     */
    private $maxRating18;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_19", type="text", nullable=true)
     */
    private $parameter19;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_19", type="text", nullable=true)
     */
    private $expectation19;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_19", type="integer", nullable=true)
     */
    private $maxRating19;

    /**
     * @var string|null
     *
     * @ORM\Column(name="parameter_20", type="text", nullable=true)
     */
    private $parameter20;

    /**
     * @var string|null
     *
     * @ORM\Column(name="expectation_20", type="text", nullable=true)
     */
    private $expectation20;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_rating_20", type="integer", nullable=true)
     */
    private $maxRating20;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_2", type="text", nullable=true)
     */
    private $appraiseeResponse2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_3", type="text", nullable=true)
     */
    private $appraiseeResponse3;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_4", type="text", nullable=true)
     */
    private $appraiseeResponse4;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_5", type="text", nullable=true)
     */
    private $appraiseeResponse5;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_6", type="text", nullable=true)
     */
    private $appraiseeResponse6;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_7", type="text", nullable=true)
     */
    private $appraiseeResponse7;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_8", type="text", nullable=true)
     */
    private $appraiseeResponse8;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_9", type="text", nullable=true)
     */
    private $appraiseeResponse9;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_10", type="text", nullable=true)
     */
    private $appraiseeResponse10;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_11", type="text", nullable=true)
     */
    private $appraiseeResponse11;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_12", type="text", nullable=true)
     */
    private $appraiseeResponse12;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_13", type="text", nullable=true)
     */
    private $appraiseeResponse13;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_14", type="text", nullable=true)
     */
    private $appraiseeResponse14;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_15", type="text", nullable=true)
     */
    private $appraiseeResponse15;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_16", type="text", nullable=true)
     */
    private $appraiseeResponse16;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_17", type="text", nullable=true)
     */
    private $appraiseeResponse17;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_18", type="text", nullable=true)
     */
    private $appraiseeResponse18;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_19", type="text", nullable=true)
     */
    private $appraiseeResponse19;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_20", type="text", nullable=true)
     */
    private $appraiseeResponse20;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_1", type="integer", nullable=true)
     */
    private $ratingObtained1;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_2", type="integer", nullable=true)
     */
    private $ratingObtained2;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_3", type="integer", nullable=true)
     */
    private $ratingObtained3;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_4", type="integer", nullable=true)
     */
    private $ratingObtained4;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_5", type="integer", nullable=true)
     */
    private $ratingObtained5;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_6", type="integer", nullable=true)
     */
    private $ratingObtained6;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_7", type="integer", nullable=true)
     */
    private $ratingObtained7;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_8", type="integer", nullable=true)
     */
    private $ratingObtained8;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_9", type="integer", nullable=true)
     */
    private $ratingObtained9;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_10", type="integer", nullable=true)
     */
    private $ratingObtained10;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_11", type="integer", nullable=true)
     */
    private $ratingObtained11;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_12", type="integer", nullable=true)
     */
    private $ratingObtained12;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_13", type="integer", nullable=true)
     */
    private $ratingObtained13;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_14", type="integer", nullable=true)
     */
    private $ratingObtained14;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_15", type="integer", nullable=true)
     */
    private $ratingObtained15;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_16", type="integer", nullable=true)
     */
    private $ratingObtained16;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_17", type="integer", nullable=true)
     */
    private $ratingObtained17;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_18", type="integer", nullable=true)
     */
    private $ratingObtained18;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_19", type="integer", nullable=true)
     */
    private $ratingObtained19;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rating_obtained_20", type="integer", nullable=true)
     */
    private $ratingObtained20;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_1", type="text", nullable=true)
     */
    private $managerRemarks1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_2", type="text", nullable=true)
     */
    private $managerRemarks2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_3", type="text", nullable=true)
     */
    private $managerRemarks3;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_4", type="text", nullable=true)
     */
    private $managerRemarks4;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_5", type="text", nullable=true)
     */
    private $managerRemarks5;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_6", type="text", nullable=true)
     */
    private $managerRemarks6;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_7", type="text", nullable=true)
     */
    private $managerRemarks7;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_8", type="text", nullable=true)
     */
    private $managerRemarks8;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_9", type="text", nullable=true)
     */
    private $managerRemarks9;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_10", type="text", nullable=true)
     */
    private $managerRemarks10;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_11", type="text", nullable=true)
     */
    private $managerRemarks11;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_12", type="text", nullable=true)
     */
    private $managerRemarks12;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_13", type="text", nullable=true)
     */
    private $managerRemarks13;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_14", type="text", nullable=true)
     */
    private $managerRemarks14;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_15", type="text", nullable=true)
     */
    private $managerRemarks15;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_16", type="text", nullable=true)
     */
    private $managerRemarks16;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_17", type="text", nullable=true)
     */
    private $managerRemarks17;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_18", type="text", nullable=true)
     */
    private $managerRemarks18;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_19", type="text", nullable=true)
     */
    private $managerRemarks19;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_remarks_20", type="text", nullable=true)
     */
    private $managerRemarks20;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_complete", type="text", nullable=true)
     */
    private $appraiseeResponseComplete;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_evaluation_complete", type="text", nullable=true)
     */
    private $managerEvaluationComplete;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewer_response_complete", type="text", nullable=true)
     */
    private $reviewerResponseComplete;

    /**
     * @var string|null
     *
     * @ORM\Column(name="appraisee_response_1", type="text", nullable=true)
     */
    private $appraiseeResponse1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reviewer_remarks", type="text", nullable=true)
     */
    private $reviewerRemarks;

    /**
     * @var string|null
     *
     * @ORM\Column(name="goalsheet_created_by", type="text", nullable=true)
     */
    private $goalsheetCreatedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="goalsheet_created_on", type="datetime", nullable=true)
     */
    private $goalsheetCreatedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="goalsheet_submitted_by", type="text", nullable=true)
     */
    private $goalsheetSubmittedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="goalsheet_submitted_on", type="datetime", nullable=true)
     */
    private $goalsheetSubmittedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="goalsheet_evaluated_by", type="text", nullable=true)
     */
    private $goalsheetEvaluatedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="goalsheet_evaluated_on", type="datetime", nullable=true)
     */
    private $goalsheetEvaluatedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="goalsheet_reviewed_by", type="text", nullable=true)
     */
    private $goalsheetReviewedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="goalsheet_reviewed_on", type="datetime", nullable=true)
     */
    private $goalsheetReviewedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipf", type="decimal", precision=10, scale=0, nullable=true)
     */
    private $ipf;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipf_response", type="text", nullable=true)
     */
    private $ipfResponse;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipf_response_by", type="text", nullable=true)
     */
    private $ipfResponseBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="ipf_response_on", type="datetime", nullable=true)
     */
    private $ipfResponseOn;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="ipf_process_closed_on", type="datetime", nullable=true)
     */
    private $ipfProcessClosedOn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ipf_process_closed_by", type="text", nullable=true)
     */
    private $ipfProcessClosedBy;

    /**
     * @var string|null
     *
     * @ORM\Column(name="unlock_request", type="text", nullable=true)
     */
    private $unlockRequest;

    /**
     * @var string|null
     *
     * @ORM\Column(name="manager_unlocked", type="text", nullable=true)
     */
    private $managerUnlocked;



    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set goalsheetid.
     *
     * @param string|null $goalsheetid
     *
     * @return AppraiseeResponse
     */
    public function setGoalsheetid($goalsheetid = null)
    {
        $this->goalsheetid = $goalsheetid;

        return $this;
    }

    /**
     * Get goalsheetid.
     *
     * @return string|null
     */
    public function getGoalsheetid()
    {
        return $this->goalsheetid;
    }

    /**
     * Set appraiseeAssociatenumber.
     *
     * @param string|null $appraiseeAssociatenumber
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeAssociatenumber($appraiseeAssociatenumber = null)
    {
        $this->appraiseeAssociatenumber = $appraiseeAssociatenumber;

        return $this;
    }

    /**
     * Get appraiseeAssociatenumber.
     *
     * @return string|null
     */
    public function getAppraiseeAssociatenumber()
    {
        return $this->appraiseeAssociatenumber;
    }

    /**
     * Set managerAssociatenumber.
     *
     * @param string|null $managerAssociatenumber
     *
     * @return AppraiseeResponse
     */
    public function setManagerAssociatenumber($managerAssociatenumber = null)
    {
        $this->managerAssociatenumber = $managerAssociatenumber;

        return $this;
    }

    /**
     * Get managerAssociatenumber.
     *
     * @return string|null
     */
    public function getManagerAssociatenumber()
    {
        return $this->managerAssociatenumber;
    }

    /**
     * Set reviewerAssociatenumber.
     *
     * @param string|null $reviewerAssociatenumber
     *
     * @return AppraiseeResponse
     */
    public function setReviewerAssociatenumber($reviewerAssociatenumber = null)
    {
        $this->reviewerAssociatenumber = $reviewerAssociatenumber;

        return $this;
    }

    /**
     * Get reviewerAssociatenumber.
     *
     * @return string|null
     */
    public function getReviewerAssociatenumber()
    {
        return $this->reviewerAssociatenumber;
    }

    /**
     * Set role.
     *
     * @param string|null $role
     *
     * @return AppraiseeResponse
     */
    public function setRole($role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role.
     *
     * @return string|null
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set appraisaltype.
     *
     * @param string|null $appraisaltype
     *
     * @return AppraiseeResponse
     */
    public function setAppraisaltype($appraisaltype = null)
    {
        $this->appraisaltype = $appraisaltype;

        return $this;
    }

    /**
     * Get appraisaltype.
     *
     * @return string|null
     */
    public function getAppraisaltype()
    {
        return $this->appraisaltype;
    }

    /**
     * Set appraisalyear.
     *
     * @param string|null $appraisalyear
     *
     * @return AppraiseeResponse
     */
    public function setAppraisalyear($appraisalyear = null)
    {
        $this->appraisalyear = $appraisalyear;

        return $this;
    }

    /**
     * Get appraisalyear.
     *
     * @return string|null
     */
    public function getAppraisalyear()
    {
        return $this->appraisalyear;
    }

    /**
     * Set parameter1.
     *
     * @param string|null $parameter1
     *
     * @return AppraiseeResponse
     */
    public function setParameter1($parameter1 = null)
    {
        $this->parameter1 = $parameter1;

        return $this;
    }

    /**
     * Get parameter1.
     *
     * @return string|null
     */
    public function getParameter1()
    {
        return $this->parameter1;
    }

    /**
     * Set expectation1.
     *
     * @param string|null $expectation1
     *
     * @return AppraiseeResponse
     */
    public function setExpectation1($expectation1 = null)
    {
        $this->expectation1 = $expectation1;

        return $this;
    }

    /**
     * Get expectation1.
     *
     * @return string|null
     */
    public function getExpectation1()
    {
        return $this->expectation1;
    }

    /**
     * Set maxRating1.
     *
     * @param int|null $maxRating1
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating1($maxRating1 = null)
    {
        $this->maxRating1 = $maxRating1;

        return $this;
    }

    /**
     * Get maxRating1.
     *
     * @return int|null
     */
    public function getMaxRating1()
    {
        return $this->maxRating1;
    }

    /**
     * Set parameter2.
     *
     * @param string|null $parameter2
     *
     * @return AppraiseeResponse
     */
    public function setParameter2($parameter2 = null)
    {
        $this->parameter2 = $parameter2;

        return $this;
    }

    /**
     * Get parameter2.
     *
     * @return string|null
     */
    public function getParameter2()
    {
        return $this->parameter2;
    }

    /**
     * Set expectation2.
     *
     * @param string|null $expectation2
     *
     * @return AppraiseeResponse
     */
    public function setExpectation2($expectation2 = null)
    {
        $this->expectation2 = $expectation2;

        return $this;
    }

    /**
     * Get expectation2.
     *
     * @return string|null
     */
    public function getExpectation2()
    {
        return $this->expectation2;
    }

    /**
     * Set maxRating2.
     *
     * @param int|null $maxRating2
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating2($maxRating2 = null)
    {
        $this->maxRating2 = $maxRating2;

        return $this;
    }

    /**
     * Get maxRating2.
     *
     * @return int|null
     */
    public function getMaxRating2()
    {
        return $this->maxRating2;
    }

    /**
     * Set parameter3.
     *
     * @param string|null $parameter3
     *
     * @return AppraiseeResponse
     */
    public function setParameter3($parameter3 = null)
    {
        $this->parameter3 = $parameter3;

        return $this;
    }

    /**
     * Get parameter3.
     *
     * @return string|null
     */
    public function getParameter3()
    {
        return $this->parameter3;
    }

    /**
     * Set expectation3.
     *
     * @param string|null $expectation3
     *
     * @return AppraiseeResponse
     */
    public function setExpectation3($expectation3 = null)
    {
        $this->expectation3 = $expectation3;

        return $this;
    }

    /**
     * Get expectation3.
     *
     * @return string|null
     */
    public function getExpectation3()
    {
        return $this->expectation3;
    }

    /**
     * Set maxRating3.
     *
     * @param int|null $maxRating3
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating3($maxRating3 = null)
    {
        $this->maxRating3 = $maxRating3;

        return $this;
    }

    /**
     * Get maxRating3.
     *
     * @return int|null
     */
    public function getMaxRating3()
    {
        return $this->maxRating3;
    }

    /**
     * Set parameter4.
     *
     * @param string|null $parameter4
     *
     * @return AppraiseeResponse
     */
    public function setParameter4($parameter4 = null)
    {
        $this->parameter4 = $parameter4;

        return $this;
    }

    /**
     * Get parameter4.
     *
     * @return string|null
     */
    public function getParameter4()
    {
        return $this->parameter4;
    }

    /**
     * Set expectation4.
     *
     * @param string|null $expectation4
     *
     * @return AppraiseeResponse
     */
    public function setExpectation4($expectation4 = null)
    {
        $this->expectation4 = $expectation4;

        return $this;
    }

    /**
     * Get expectation4.
     *
     * @return string|null
     */
    public function getExpectation4()
    {
        return $this->expectation4;
    }

    /**
     * Set maxRating4.
     *
     * @param int|null $maxRating4
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating4($maxRating4 = null)
    {
        $this->maxRating4 = $maxRating4;

        return $this;
    }

    /**
     * Get maxRating4.
     *
     * @return int|null
     */
    public function getMaxRating4()
    {
        return $this->maxRating4;
    }

    /**
     * Set parameter5.
     *
     * @param string|null $parameter5
     *
     * @return AppraiseeResponse
     */
    public function setParameter5($parameter5 = null)
    {
        $this->parameter5 = $parameter5;

        return $this;
    }

    /**
     * Get parameter5.
     *
     * @return string|null
     */
    public function getParameter5()
    {
        return $this->parameter5;
    }

    /**
     * Set expectation5.
     *
     * @param string|null $expectation5
     *
     * @return AppraiseeResponse
     */
    public function setExpectation5($expectation5 = null)
    {
        $this->expectation5 = $expectation5;

        return $this;
    }

    /**
     * Get expectation5.
     *
     * @return string|null
     */
    public function getExpectation5()
    {
        return $this->expectation5;
    }

    /**
     * Set maxRating5.
     *
     * @param int|null $maxRating5
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating5($maxRating5 = null)
    {
        $this->maxRating5 = $maxRating5;

        return $this;
    }

    /**
     * Get maxRating5.
     *
     * @return int|null
     */
    public function getMaxRating5()
    {
        return $this->maxRating5;
    }

    /**
     * Set parameter6.
     *
     * @param string|null $parameter6
     *
     * @return AppraiseeResponse
     */
    public function setParameter6($parameter6 = null)
    {
        $this->parameter6 = $parameter6;

        return $this;
    }

    /**
     * Get parameter6.
     *
     * @return string|null
     */
    public function getParameter6()
    {
        return $this->parameter6;
    }

    /**
     * Set expectation6.
     *
     * @param string|null $expectation6
     *
     * @return AppraiseeResponse
     */
    public function setExpectation6($expectation6 = null)
    {
        $this->expectation6 = $expectation6;

        return $this;
    }

    /**
     * Get expectation6.
     *
     * @return string|null
     */
    public function getExpectation6()
    {
        return $this->expectation6;
    }

    /**
     * Set maxRating6.
     *
     * @param int|null $maxRating6
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating6($maxRating6 = null)
    {
        $this->maxRating6 = $maxRating6;

        return $this;
    }

    /**
     * Get maxRating6.
     *
     * @return int|null
     */
    public function getMaxRating6()
    {
        return $this->maxRating6;
    }

    /**
     * Set parameter7.
     *
     * @param string|null $parameter7
     *
     * @return AppraiseeResponse
     */
    public function setParameter7($parameter7 = null)
    {
        $this->parameter7 = $parameter7;

        return $this;
    }

    /**
     * Get parameter7.
     *
     * @return string|null
     */
    public function getParameter7()
    {
        return $this->parameter7;
    }

    /**
     * Set expectation7.
     *
     * @param string|null $expectation7
     *
     * @return AppraiseeResponse
     */
    public function setExpectation7($expectation7 = null)
    {
        $this->expectation7 = $expectation7;

        return $this;
    }

    /**
     * Get expectation7.
     *
     * @return string|null
     */
    public function getExpectation7()
    {
        return $this->expectation7;
    }

    /**
     * Set maxRating7.
     *
     * @param int|null $maxRating7
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating7($maxRating7 = null)
    {
        $this->maxRating7 = $maxRating7;

        return $this;
    }

    /**
     * Get maxRating7.
     *
     * @return int|null
     */
    public function getMaxRating7()
    {
        return $this->maxRating7;
    }

    /**
     * Set parameter8.
     *
     * @param string|null $parameter8
     *
     * @return AppraiseeResponse
     */
    public function setParameter8($parameter8 = null)
    {
        $this->parameter8 = $parameter8;

        return $this;
    }

    /**
     * Get parameter8.
     *
     * @return string|null
     */
    public function getParameter8()
    {
        return $this->parameter8;
    }

    /**
     * Set expectation8.
     *
     * @param string|null $expectation8
     *
     * @return AppraiseeResponse
     */
    public function setExpectation8($expectation8 = null)
    {
        $this->expectation8 = $expectation8;

        return $this;
    }

    /**
     * Get expectation8.
     *
     * @return string|null
     */
    public function getExpectation8()
    {
        return $this->expectation8;
    }

    /**
     * Set maxRating8.
     *
     * @param int|null $maxRating8
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating8($maxRating8 = null)
    {
        $this->maxRating8 = $maxRating8;

        return $this;
    }

    /**
     * Get maxRating8.
     *
     * @return int|null
     */
    public function getMaxRating8()
    {
        return $this->maxRating8;
    }

    /**
     * Set parameter9.
     *
     * @param string|null $parameter9
     *
     * @return AppraiseeResponse
     */
    public function setParameter9($parameter9 = null)
    {
        $this->parameter9 = $parameter9;

        return $this;
    }

    /**
     * Get parameter9.
     *
     * @return string|null
     */
    public function getParameter9()
    {
        return $this->parameter9;
    }

    /**
     * Set expectation9.
     *
     * @param string|null $expectation9
     *
     * @return AppraiseeResponse
     */
    public function setExpectation9($expectation9 = null)
    {
        $this->expectation9 = $expectation9;

        return $this;
    }

    /**
     * Get expectation9.
     *
     * @return string|null
     */
    public function getExpectation9()
    {
        return $this->expectation9;
    }

    /**
     * Set maxRating9.
     *
     * @param int|null $maxRating9
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating9($maxRating9 = null)
    {
        $this->maxRating9 = $maxRating9;

        return $this;
    }

    /**
     * Get maxRating9.
     *
     * @return int|null
     */
    public function getMaxRating9()
    {
        return $this->maxRating9;
    }

    /**
     * Set parameter10.
     *
     * @param string|null $parameter10
     *
     * @return AppraiseeResponse
     */
    public function setParameter10($parameter10 = null)
    {
        $this->parameter10 = $parameter10;

        return $this;
    }

    /**
     * Get parameter10.
     *
     * @return string|null
     */
    public function getParameter10()
    {
        return $this->parameter10;
    }

    /**
     * Set expectation10.
     *
     * @param string|null $expectation10
     *
     * @return AppraiseeResponse
     */
    public function setExpectation10($expectation10 = null)
    {
        $this->expectation10 = $expectation10;

        return $this;
    }

    /**
     * Get expectation10.
     *
     * @return string|null
     */
    public function getExpectation10()
    {
        return $this->expectation10;
    }

    /**
     * Set maxRating10.
     *
     * @param int|null $maxRating10
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating10($maxRating10 = null)
    {
        $this->maxRating10 = $maxRating10;

        return $this;
    }

    /**
     * Get maxRating10.
     *
     * @return int|null
     */
    public function getMaxRating10()
    {
        return $this->maxRating10;
    }

    /**
     * Set parameter11.
     *
     * @param string|null $parameter11
     *
     * @return AppraiseeResponse
     */
    public function setParameter11($parameter11 = null)
    {
        $this->parameter11 = $parameter11;

        return $this;
    }

    /**
     * Get parameter11.
     *
     * @return string|null
     */
    public function getParameter11()
    {
        return $this->parameter11;
    }

    /**
     * Set expectation11.
     *
     * @param string|null $expectation11
     *
     * @return AppraiseeResponse
     */
    public function setExpectation11($expectation11 = null)
    {
        $this->expectation11 = $expectation11;

        return $this;
    }

    /**
     * Get expectation11.
     *
     * @return string|null
     */
    public function getExpectation11()
    {
        return $this->expectation11;
    }

    /**
     * Set maxRating11.
     *
     * @param int|null $maxRating11
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating11($maxRating11 = null)
    {
        $this->maxRating11 = $maxRating11;

        return $this;
    }

    /**
     * Get maxRating11.
     *
     * @return int|null
     */
    public function getMaxRating11()
    {
        return $this->maxRating11;
    }

    /**
     * Set parameter12.
     *
     * @param string|null $parameter12
     *
     * @return AppraiseeResponse
     */
    public function setParameter12($parameter12 = null)
    {
        $this->parameter12 = $parameter12;

        return $this;
    }

    /**
     * Get parameter12.
     *
     * @return string|null
     */
    public function getParameter12()
    {
        return $this->parameter12;
    }

    /**
     * Set expectation12.
     *
     * @param string|null $expectation12
     *
     * @return AppraiseeResponse
     */
    public function setExpectation12($expectation12 = null)
    {
        $this->expectation12 = $expectation12;

        return $this;
    }

    /**
     * Get expectation12.
     *
     * @return string|null
     */
    public function getExpectation12()
    {
        return $this->expectation12;
    }

    /**
     * Set maxRating12.
     *
     * @param int|null $maxRating12
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating12($maxRating12 = null)
    {
        $this->maxRating12 = $maxRating12;

        return $this;
    }

    /**
     * Get maxRating12.
     *
     * @return int|null
     */
    public function getMaxRating12()
    {
        return $this->maxRating12;
    }

    /**
     * Set parameter13.
     *
     * @param string|null $parameter13
     *
     * @return AppraiseeResponse
     */
    public function setParameter13($parameter13 = null)
    {
        $this->parameter13 = $parameter13;

        return $this;
    }

    /**
     * Get parameter13.
     *
     * @return string|null
     */
    public function getParameter13()
    {
        return $this->parameter13;
    }

    /**
     * Set expectation13.
     *
     * @param string|null $expectation13
     *
     * @return AppraiseeResponse
     */
    public function setExpectation13($expectation13 = null)
    {
        $this->expectation13 = $expectation13;

        return $this;
    }

    /**
     * Get expectation13.
     *
     * @return string|null
     */
    public function getExpectation13()
    {
        return $this->expectation13;
    }

    /**
     * Set maxRating13.
     *
     * @param int|null $maxRating13
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating13($maxRating13 = null)
    {
        $this->maxRating13 = $maxRating13;

        return $this;
    }

    /**
     * Get maxRating13.
     *
     * @return int|null
     */
    public function getMaxRating13()
    {
        return $this->maxRating13;
    }

    /**
     * Set parameter14.
     *
     * @param string|null $parameter14
     *
     * @return AppraiseeResponse
     */
    public function setParameter14($parameter14 = null)
    {
        $this->parameter14 = $parameter14;

        return $this;
    }

    /**
     * Get parameter14.
     *
     * @return string|null
     */
    public function getParameter14()
    {
        return $this->parameter14;
    }

    /**
     * Set expectation14.
     *
     * @param string|null $expectation14
     *
     * @return AppraiseeResponse
     */
    public function setExpectation14($expectation14 = null)
    {
        $this->expectation14 = $expectation14;

        return $this;
    }

    /**
     * Get expectation14.
     *
     * @return string|null
     */
    public function getExpectation14()
    {
        return $this->expectation14;
    }

    /**
     * Set maxRating14.
     *
     * @param int|null $maxRating14
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating14($maxRating14 = null)
    {
        $this->maxRating14 = $maxRating14;

        return $this;
    }

    /**
     * Get maxRating14.
     *
     * @return int|null
     */
    public function getMaxRating14()
    {
        return $this->maxRating14;
    }

    /**
     * Set parameter15.
     *
     * @param string|null $parameter15
     *
     * @return AppraiseeResponse
     */
    public function setParameter15($parameter15 = null)
    {
        $this->parameter15 = $parameter15;

        return $this;
    }

    /**
     * Get parameter15.
     *
     * @return string|null
     */
    public function getParameter15()
    {
        return $this->parameter15;
    }

    /**
     * Set expectation15.
     *
     * @param string|null $expectation15
     *
     * @return AppraiseeResponse
     */
    public function setExpectation15($expectation15 = null)
    {
        $this->expectation15 = $expectation15;

        return $this;
    }

    /**
     * Get expectation15.
     *
     * @return string|null
     */
    public function getExpectation15()
    {
        return $this->expectation15;
    }

    /**
     * Set maxRating15.
     *
     * @param int|null $maxRating15
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating15($maxRating15 = null)
    {
        $this->maxRating15 = $maxRating15;

        return $this;
    }

    /**
     * Get maxRating15.
     *
     * @return int|null
     */
    public function getMaxRating15()
    {
        return $this->maxRating15;
    }

    /**
     * Set parameter16.
     *
     * @param string|null $parameter16
     *
     * @return AppraiseeResponse
     */
    public function setParameter16($parameter16 = null)
    {
        $this->parameter16 = $parameter16;

        return $this;
    }

    /**
     * Get parameter16.
     *
     * @return string|null
     */
    public function getParameter16()
    {
        return $this->parameter16;
    }

    /**
     * Set expectation16.
     *
     * @param string|null $expectation16
     *
     * @return AppraiseeResponse
     */
    public function setExpectation16($expectation16 = null)
    {
        $this->expectation16 = $expectation16;

        return $this;
    }

    /**
     * Get expectation16.
     *
     * @return string|null
     */
    public function getExpectation16()
    {
        return $this->expectation16;
    }

    /**
     * Set maxRating16.
     *
     * @param int|null $maxRating16
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating16($maxRating16 = null)
    {
        $this->maxRating16 = $maxRating16;

        return $this;
    }

    /**
     * Get maxRating16.
     *
     * @return int|null
     */
    public function getMaxRating16()
    {
        return $this->maxRating16;
    }

    /**
     * Set parameter17.
     *
     * @param string|null $parameter17
     *
     * @return AppraiseeResponse
     */
    public function setParameter17($parameter17 = null)
    {
        $this->parameter17 = $parameter17;

        return $this;
    }

    /**
     * Get parameter17.
     *
     * @return string|null
     */
    public function getParameter17()
    {
        return $this->parameter17;
    }

    /**
     * Set expectation17.
     *
     * @param string|null $expectation17
     *
     * @return AppraiseeResponse
     */
    public function setExpectation17($expectation17 = null)
    {
        $this->expectation17 = $expectation17;

        return $this;
    }

    /**
     * Get expectation17.
     *
     * @return string|null
     */
    public function getExpectation17()
    {
        return $this->expectation17;
    }

    /**
     * Set maxRating17.
     *
     * @param int|null $maxRating17
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating17($maxRating17 = null)
    {
        $this->maxRating17 = $maxRating17;

        return $this;
    }

    /**
     * Get maxRating17.
     *
     * @return int|null
     */
    public function getMaxRating17()
    {
        return $this->maxRating17;
    }

    /**
     * Set parameter18.
     *
     * @param string|null $parameter18
     *
     * @return AppraiseeResponse
     */
    public function setParameter18($parameter18 = null)
    {
        $this->parameter18 = $parameter18;

        return $this;
    }

    /**
     * Get parameter18.
     *
     * @return string|null
     */
    public function getParameter18()
    {
        return $this->parameter18;
    }

    /**
     * Set expectation18.
     *
     * @param string|null $expectation18
     *
     * @return AppraiseeResponse
     */
    public function setExpectation18($expectation18 = null)
    {
        $this->expectation18 = $expectation18;

        return $this;
    }

    /**
     * Get expectation18.
     *
     * @return string|null
     */
    public function getExpectation18()
    {
        return $this->expectation18;
    }

    /**
     * Set maxRating18.
     *
     * @param int|null $maxRating18
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating18($maxRating18 = null)
    {
        $this->maxRating18 = $maxRating18;

        return $this;
    }

    /**
     * Get maxRating18.
     *
     * @return int|null
     */
    public function getMaxRating18()
    {
        return $this->maxRating18;
    }

    /**
     * Set parameter19.
     *
     * @param string|null $parameter19
     *
     * @return AppraiseeResponse
     */
    public function setParameter19($parameter19 = null)
    {
        $this->parameter19 = $parameter19;

        return $this;
    }

    /**
     * Get parameter19.
     *
     * @return string|null
     */
    public function getParameter19()
    {
        return $this->parameter19;
    }

    /**
     * Set expectation19.
     *
     * @param string|null $expectation19
     *
     * @return AppraiseeResponse
     */
    public function setExpectation19($expectation19 = null)
    {
        $this->expectation19 = $expectation19;

        return $this;
    }

    /**
     * Get expectation19.
     *
     * @return string|null
     */
    public function getExpectation19()
    {
        return $this->expectation19;
    }

    /**
     * Set maxRating19.
     *
     * @param int|null $maxRating19
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating19($maxRating19 = null)
    {
        $this->maxRating19 = $maxRating19;

        return $this;
    }

    /**
     * Get maxRating19.
     *
     * @return int|null
     */
    public function getMaxRating19()
    {
        return $this->maxRating19;
    }

    /**
     * Set parameter20.
     *
     * @param string|null $parameter20
     *
     * @return AppraiseeResponse
     */
    public function setParameter20($parameter20 = null)
    {
        $this->parameter20 = $parameter20;

        return $this;
    }

    /**
     * Get parameter20.
     *
     * @return string|null
     */
    public function getParameter20()
    {
        return $this->parameter20;
    }

    /**
     * Set expectation20.
     *
     * @param string|null $expectation20
     *
     * @return AppraiseeResponse
     */
    public function setExpectation20($expectation20 = null)
    {
        $this->expectation20 = $expectation20;

        return $this;
    }

    /**
     * Get expectation20.
     *
     * @return string|null
     */
    public function getExpectation20()
    {
        return $this->expectation20;
    }

    /**
     * Set maxRating20.
     *
     * @param int|null $maxRating20
     *
     * @return AppraiseeResponse
     */
    public function setMaxRating20($maxRating20 = null)
    {
        $this->maxRating20 = $maxRating20;

        return $this;
    }

    /**
     * Get maxRating20.
     *
     * @return int|null
     */
    public function getMaxRating20()
    {
        return $this->maxRating20;
    }

    /**
     * Set appraiseeResponse2.
     *
     * @param string|null $appraiseeResponse2
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse2($appraiseeResponse2 = null)
    {
        $this->appraiseeResponse2 = $appraiseeResponse2;

        return $this;
    }

    /**
     * Get appraiseeResponse2.
     *
     * @return string|null
     */
    public function getAppraiseeResponse2()
    {
        return $this->appraiseeResponse2;
    }

    /**
     * Set appraiseeResponse3.
     *
     * @param string|null $appraiseeResponse3
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse3($appraiseeResponse3 = null)
    {
        $this->appraiseeResponse3 = $appraiseeResponse3;

        return $this;
    }

    /**
     * Get appraiseeResponse3.
     *
     * @return string|null
     */
    public function getAppraiseeResponse3()
    {
        return $this->appraiseeResponse3;
    }

    /**
     * Set appraiseeResponse4.
     *
     * @param string|null $appraiseeResponse4
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse4($appraiseeResponse4 = null)
    {
        $this->appraiseeResponse4 = $appraiseeResponse4;

        return $this;
    }

    /**
     * Get appraiseeResponse4.
     *
     * @return string|null
     */
    public function getAppraiseeResponse4()
    {
        return $this->appraiseeResponse4;
    }

    /**
     * Set appraiseeResponse5.
     *
     * @param string|null $appraiseeResponse5
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse5($appraiseeResponse5 = null)
    {
        $this->appraiseeResponse5 = $appraiseeResponse5;

        return $this;
    }

    /**
     * Get appraiseeResponse5.
     *
     * @return string|null
     */
    public function getAppraiseeResponse5()
    {
        return $this->appraiseeResponse5;
    }

    /**
     * Set appraiseeResponse6.
     *
     * @param string|null $appraiseeResponse6
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse6($appraiseeResponse6 = null)
    {
        $this->appraiseeResponse6 = $appraiseeResponse6;

        return $this;
    }

    /**
     * Get appraiseeResponse6.
     *
     * @return string|null
     */
    public function getAppraiseeResponse6()
    {
        return $this->appraiseeResponse6;
    }

    /**
     * Set appraiseeResponse7.
     *
     * @param string|null $appraiseeResponse7
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse7($appraiseeResponse7 = null)
    {
        $this->appraiseeResponse7 = $appraiseeResponse7;

        return $this;
    }

    /**
     * Get appraiseeResponse7.
     *
     * @return string|null
     */
    public function getAppraiseeResponse7()
    {
        return $this->appraiseeResponse7;
    }

    /**
     * Set appraiseeResponse8.
     *
     * @param string|null $appraiseeResponse8
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse8($appraiseeResponse8 = null)
    {
        $this->appraiseeResponse8 = $appraiseeResponse8;

        return $this;
    }

    /**
     * Get appraiseeResponse8.
     *
     * @return string|null
     */
    public function getAppraiseeResponse8()
    {
        return $this->appraiseeResponse8;
    }

    /**
     * Set appraiseeResponse9.
     *
     * @param string|null $appraiseeResponse9
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse9($appraiseeResponse9 = null)
    {
        $this->appraiseeResponse9 = $appraiseeResponse9;

        return $this;
    }

    /**
     * Get appraiseeResponse9.
     *
     * @return string|null
     */
    public function getAppraiseeResponse9()
    {
        return $this->appraiseeResponse9;
    }

    /**
     * Set appraiseeResponse10.
     *
     * @param string|null $appraiseeResponse10
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse10($appraiseeResponse10 = null)
    {
        $this->appraiseeResponse10 = $appraiseeResponse10;

        return $this;
    }

    /**
     * Get appraiseeResponse10.
     *
     * @return string|null
     */
    public function getAppraiseeResponse10()
    {
        return $this->appraiseeResponse10;
    }

    /**
     * Set appraiseeResponse11.
     *
     * @param string|null $appraiseeResponse11
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse11($appraiseeResponse11 = null)
    {
        $this->appraiseeResponse11 = $appraiseeResponse11;

        return $this;
    }

    /**
     * Get appraiseeResponse11.
     *
     * @return string|null
     */
    public function getAppraiseeResponse11()
    {
        return $this->appraiseeResponse11;
    }

    /**
     * Set appraiseeResponse12.
     *
     * @param string|null $appraiseeResponse12
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse12($appraiseeResponse12 = null)
    {
        $this->appraiseeResponse12 = $appraiseeResponse12;

        return $this;
    }

    /**
     * Get appraiseeResponse12.
     *
     * @return string|null
     */
    public function getAppraiseeResponse12()
    {
        return $this->appraiseeResponse12;
    }

    /**
     * Set appraiseeResponse13.
     *
     * @param string|null $appraiseeResponse13
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse13($appraiseeResponse13 = null)
    {
        $this->appraiseeResponse13 = $appraiseeResponse13;

        return $this;
    }

    /**
     * Get appraiseeResponse13.
     *
     * @return string|null
     */
    public function getAppraiseeResponse13()
    {
        return $this->appraiseeResponse13;
    }

    /**
     * Set appraiseeResponse14.
     *
     * @param string|null $appraiseeResponse14
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse14($appraiseeResponse14 = null)
    {
        $this->appraiseeResponse14 = $appraiseeResponse14;

        return $this;
    }

    /**
     * Get appraiseeResponse14.
     *
     * @return string|null
     */
    public function getAppraiseeResponse14()
    {
        return $this->appraiseeResponse14;
    }

    /**
     * Set appraiseeResponse15.
     *
     * @param string|null $appraiseeResponse15
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse15($appraiseeResponse15 = null)
    {
        $this->appraiseeResponse15 = $appraiseeResponse15;

        return $this;
    }

    /**
     * Get appraiseeResponse15.
     *
     * @return string|null
     */
    public function getAppraiseeResponse15()
    {
        return $this->appraiseeResponse15;
    }

    /**
     * Set appraiseeResponse16.
     *
     * @param string|null $appraiseeResponse16
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse16($appraiseeResponse16 = null)
    {
        $this->appraiseeResponse16 = $appraiseeResponse16;

        return $this;
    }

    /**
     * Get appraiseeResponse16.
     *
     * @return string|null
     */
    public function getAppraiseeResponse16()
    {
        return $this->appraiseeResponse16;
    }

    /**
     * Set appraiseeResponse17.
     *
     * @param string|null $appraiseeResponse17
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse17($appraiseeResponse17 = null)
    {
        $this->appraiseeResponse17 = $appraiseeResponse17;

        return $this;
    }

    /**
     * Get appraiseeResponse17.
     *
     * @return string|null
     */
    public function getAppraiseeResponse17()
    {
        return $this->appraiseeResponse17;
    }

    /**
     * Set appraiseeResponse18.
     *
     * @param string|null $appraiseeResponse18
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse18($appraiseeResponse18 = null)
    {
        $this->appraiseeResponse18 = $appraiseeResponse18;

        return $this;
    }

    /**
     * Get appraiseeResponse18.
     *
     * @return string|null
     */
    public function getAppraiseeResponse18()
    {
        return $this->appraiseeResponse18;
    }

    /**
     * Set appraiseeResponse19.
     *
     * @param string|null $appraiseeResponse19
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse19($appraiseeResponse19 = null)
    {
        $this->appraiseeResponse19 = $appraiseeResponse19;

        return $this;
    }

    /**
     * Get appraiseeResponse19.
     *
     * @return string|null
     */
    public function getAppraiseeResponse19()
    {
        return $this->appraiseeResponse19;
    }

    /**
     * Set appraiseeResponse20.
     *
     * @param string|null $appraiseeResponse20
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse20($appraiseeResponse20 = null)
    {
        $this->appraiseeResponse20 = $appraiseeResponse20;

        return $this;
    }

    /**
     * Get appraiseeResponse20.
     *
     * @return string|null
     */
    public function getAppraiseeResponse20()
    {
        return $this->appraiseeResponse20;
    }

    /**
     * Set ratingObtained1.
     *
     * @param int|null $ratingObtained1
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained1($ratingObtained1 = null)
    {
        $this->ratingObtained1 = $ratingObtained1;

        return $this;
    }

    /**
     * Get ratingObtained1.
     *
     * @return int|null
     */
    public function getRatingObtained1()
    {
        return $this->ratingObtained1;
    }

    /**
     * Set ratingObtained2.
     *
     * @param int|null $ratingObtained2
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained2($ratingObtained2 = null)
    {
        $this->ratingObtained2 = $ratingObtained2;

        return $this;
    }

    /**
     * Get ratingObtained2.
     *
     * @return int|null
     */
    public function getRatingObtained2()
    {
        return $this->ratingObtained2;
    }

    /**
     * Set ratingObtained3.
     *
     * @param int|null $ratingObtained3
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained3($ratingObtained3 = null)
    {
        $this->ratingObtained3 = $ratingObtained3;

        return $this;
    }

    /**
     * Get ratingObtained3.
     *
     * @return int|null
     */
    public function getRatingObtained3()
    {
        return $this->ratingObtained3;
    }

    /**
     * Set ratingObtained4.
     *
     * @param int|null $ratingObtained4
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained4($ratingObtained4 = null)
    {
        $this->ratingObtained4 = $ratingObtained4;

        return $this;
    }

    /**
     * Get ratingObtained4.
     *
     * @return int|null
     */
    public function getRatingObtained4()
    {
        return $this->ratingObtained4;
    }

    /**
     * Set ratingObtained5.
     *
     * @param int|null $ratingObtained5
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained5($ratingObtained5 = null)
    {
        $this->ratingObtained5 = $ratingObtained5;

        return $this;
    }

    /**
     * Get ratingObtained5.
     *
     * @return int|null
     */
    public function getRatingObtained5()
    {
        return $this->ratingObtained5;
    }

    /**
     * Set ratingObtained6.
     *
     * @param int|null $ratingObtained6
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained6($ratingObtained6 = null)
    {
        $this->ratingObtained6 = $ratingObtained6;

        return $this;
    }

    /**
     * Get ratingObtained6.
     *
     * @return int|null
     */
    public function getRatingObtained6()
    {
        return $this->ratingObtained6;
    }

    /**
     * Set ratingObtained7.
     *
     * @param int|null $ratingObtained7
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained7($ratingObtained7 = null)
    {
        $this->ratingObtained7 = $ratingObtained7;

        return $this;
    }

    /**
     * Get ratingObtained7.
     *
     * @return int|null
     */
    public function getRatingObtained7()
    {
        return $this->ratingObtained7;
    }

    /**
     * Set ratingObtained8.
     *
     * @param int|null $ratingObtained8
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained8($ratingObtained8 = null)
    {
        $this->ratingObtained8 = $ratingObtained8;

        return $this;
    }

    /**
     * Get ratingObtained8.
     *
     * @return int|null
     */
    public function getRatingObtained8()
    {
        return $this->ratingObtained8;
    }

    /**
     * Set ratingObtained9.
     *
     * @param int|null $ratingObtained9
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained9($ratingObtained9 = null)
    {
        $this->ratingObtained9 = $ratingObtained9;

        return $this;
    }

    /**
     * Get ratingObtained9.
     *
     * @return int|null
     */
    public function getRatingObtained9()
    {
        return $this->ratingObtained9;
    }

    /**
     * Set ratingObtained10.
     *
     * @param int|null $ratingObtained10
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained10($ratingObtained10 = null)
    {
        $this->ratingObtained10 = $ratingObtained10;

        return $this;
    }

    /**
     * Get ratingObtained10.
     *
     * @return int|null
     */
    public function getRatingObtained10()
    {
        return $this->ratingObtained10;
    }

    /**
     * Set ratingObtained11.
     *
     * @param int|null $ratingObtained11
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained11($ratingObtained11 = null)
    {
        $this->ratingObtained11 = $ratingObtained11;

        return $this;
    }

    /**
     * Get ratingObtained11.
     *
     * @return int|null
     */
    public function getRatingObtained11()
    {
        return $this->ratingObtained11;
    }

    /**
     * Set ratingObtained12.
     *
     * @param int|null $ratingObtained12
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained12($ratingObtained12 = null)
    {
        $this->ratingObtained12 = $ratingObtained12;

        return $this;
    }

    /**
     * Get ratingObtained12.
     *
     * @return int|null
     */
    public function getRatingObtained12()
    {
        return $this->ratingObtained12;
    }

    /**
     * Set ratingObtained13.
     *
     * @param int|null $ratingObtained13
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained13($ratingObtained13 = null)
    {
        $this->ratingObtained13 = $ratingObtained13;

        return $this;
    }

    /**
     * Get ratingObtained13.
     *
     * @return int|null
     */
    public function getRatingObtained13()
    {
        return $this->ratingObtained13;
    }

    /**
     * Set ratingObtained14.
     *
     * @param int|null $ratingObtained14
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained14($ratingObtained14 = null)
    {
        $this->ratingObtained14 = $ratingObtained14;

        return $this;
    }

    /**
     * Get ratingObtained14.
     *
     * @return int|null
     */
    public function getRatingObtained14()
    {
        return $this->ratingObtained14;
    }

    /**
     * Set ratingObtained15.
     *
     * @param int|null $ratingObtained15
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained15($ratingObtained15 = null)
    {
        $this->ratingObtained15 = $ratingObtained15;

        return $this;
    }

    /**
     * Get ratingObtained15.
     *
     * @return int|null
     */
    public function getRatingObtained15()
    {
        return $this->ratingObtained15;
    }

    /**
     * Set ratingObtained16.
     *
     * @param int|null $ratingObtained16
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained16($ratingObtained16 = null)
    {
        $this->ratingObtained16 = $ratingObtained16;

        return $this;
    }

    /**
     * Get ratingObtained16.
     *
     * @return int|null
     */
    public function getRatingObtained16()
    {
        return $this->ratingObtained16;
    }

    /**
     * Set ratingObtained17.
     *
     * @param int|null $ratingObtained17
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained17($ratingObtained17 = null)
    {
        $this->ratingObtained17 = $ratingObtained17;

        return $this;
    }

    /**
     * Get ratingObtained17.
     *
     * @return int|null
     */
    public function getRatingObtained17()
    {
        return $this->ratingObtained17;
    }

    /**
     * Set ratingObtained18.
     *
     * @param int|null $ratingObtained18
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained18($ratingObtained18 = null)
    {
        $this->ratingObtained18 = $ratingObtained18;

        return $this;
    }

    /**
     * Get ratingObtained18.
     *
     * @return int|null
     */
    public function getRatingObtained18()
    {
        return $this->ratingObtained18;
    }

    /**
     * Set ratingObtained19.
     *
     * @param int|null $ratingObtained19
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained19($ratingObtained19 = null)
    {
        $this->ratingObtained19 = $ratingObtained19;

        return $this;
    }

    /**
     * Get ratingObtained19.
     *
     * @return int|null
     */
    public function getRatingObtained19()
    {
        return $this->ratingObtained19;
    }

    /**
     * Set ratingObtained20.
     *
     * @param int|null $ratingObtained20
     *
     * @return AppraiseeResponse
     */
    public function setRatingObtained20($ratingObtained20 = null)
    {
        $this->ratingObtained20 = $ratingObtained20;

        return $this;
    }

    /**
     * Get ratingObtained20.
     *
     * @return int|null
     */
    public function getRatingObtained20()
    {
        return $this->ratingObtained20;
    }

    /**
     * Set managerRemarks1.
     *
     * @param string|null $managerRemarks1
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks1($managerRemarks1 = null)
    {
        $this->managerRemarks1 = $managerRemarks1;

        return $this;
    }

    /**
     * Get managerRemarks1.
     *
     * @return string|null
     */
    public function getManagerRemarks1()
    {
        return $this->managerRemarks1;
    }

    /**
     * Set managerRemarks2.
     *
     * @param string|null $managerRemarks2
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks2($managerRemarks2 = null)
    {
        $this->managerRemarks2 = $managerRemarks2;

        return $this;
    }

    /**
     * Get managerRemarks2.
     *
     * @return string|null
     */
    public function getManagerRemarks2()
    {
        return $this->managerRemarks2;
    }

    /**
     * Set managerRemarks3.
     *
     * @param string|null $managerRemarks3
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks3($managerRemarks3 = null)
    {
        $this->managerRemarks3 = $managerRemarks3;

        return $this;
    }

    /**
     * Get managerRemarks3.
     *
     * @return string|null
     */
    public function getManagerRemarks3()
    {
        return $this->managerRemarks3;
    }

    /**
     * Set managerRemarks4.
     *
     * @param string|null $managerRemarks4
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks4($managerRemarks4 = null)
    {
        $this->managerRemarks4 = $managerRemarks4;

        return $this;
    }

    /**
     * Get managerRemarks4.
     *
     * @return string|null
     */
    public function getManagerRemarks4()
    {
        return $this->managerRemarks4;
    }

    /**
     * Set managerRemarks5.
     *
     * @param string|null $managerRemarks5
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks5($managerRemarks5 = null)
    {
        $this->managerRemarks5 = $managerRemarks5;

        return $this;
    }

    /**
     * Get managerRemarks5.
     *
     * @return string|null
     */
    public function getManagerRemarks5()
    {
        return $this->managerRemarks5;
    }

    /**
     * Set managerRemarks6.
     *
     * @param string|null $managerRemarks6
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks6($managerRemarks6 = null)
    {
        $this->managerRemarks6 = $managerRemarks6;

        return $this;
    }

    /**
     * Get managerRemarks6.
     *
     * @return string|null
     */
    public function getManagerRemarks6()
    {
        return $this->managerRemarks6;
    }

    /**
     * Set managerRemarks7.
     *
     * @param string|null $managerRemarks7
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks7($managerRemarks7 = null)
    {
        $this->managerRemarks7 = $managerRemarks7;

        return $this;
    }

    /**
     * Get managerRemarks7.
     *
     * @return string|null
     */
    public function getManagerRemarks7()
    {
        return $this->managerRemarks7;
    }

    /**
     * Set managerRemarks8.
     *
     * @param string|null $managerRemarks8
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks8($managerRemarks8 = null)
    {
        $this->managerRemarks8 = $managerRemarks8;

        return $this;
    }

    /**
     * Get managerRemarks8.
     *
     * @return string|null
     */
    public function getManagerRemarks8()
    {
        return $this->managerRemarks8;
    }

    /**
     * Set managerRemarks9.
     *
     * @param string|null $managerRemarks9
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks9($managerRemarks9 = null)
    {
        $this->managerRemarks9 = $managerRemarks9;

        return $this;
    }

    /**
     * Get managerRemarks9.
     *
     * @return string|null
     */
    public function getManagerRemarks9()
    {
        return $this->managerRemarks9;
    }

    /**
     * Set managerRemarks10.
     *
     * @param string|null $managerRemarks10
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks10($managerRemarks10 = null)
    {
        $this->managerRemarks10 = $managerRemarks10;

        return $this;
    }

    /**
     * Get managerRemarks10.
     *
     * @return string|null
     */
    public function getManagerRemarks10()
    {
        return $this->managerRemarks10;
    }

    /**
     * Set managerRemarks11.
     *
     * @param string|null $managerRemarks11
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks11($managerRemarks11 = null)
    {
        $this->managerRemarks11 = $managerRemarks11;

        return $this;
    }

    /**
     * Get managerRemarks11.
     *
     * @return string|null
     */
    public function getManagerRemarks11()
    {
        return $this->managerRemarks11;
    }

    /**
     * Set managerRemarks12.
     *
     * @param string|null $managerRemarks12
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks12($managerRemarks12 = null)
    {
        $this->managerRemarks12 = $managerRemarks12;

        return $this;
    }

    /**
     * Get managerRemarks12.
     *
     * @return string|null
     */
    public function getManagerRemarks12()
    {
        return $this->managerRemarks12;
    }

    /**
     * Set managerRemarks13.
     *
     * @param string|null $managerRemarks13
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks13($managerRemarks13 = null)
    {
        $this->managerRemarks13 = $managerRemarks13;

        return $this;
    }

    /**
     * Get managerRemarks13.
     *
     * @return string|null
     */
    public function getManagerRemarks13()
    {
        return $this->managerRemarks13;
    }

    /**
     * Set managerRemarks14.
     *
     * @param string|null $managerRemarks14
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks14($managerRemarks14 = null)
    {
        $this->managerRemarks14 = $managerRemarks14;

        return $this;
    }

    /**
     * Get managerRemarks14.
     *
     * @return string|null
     */
    public function getManagerRemarks14()
    {
        return $this->managerRemarks14;
    }

    /**
     * Set managerRemarks15.
     *
     * @param string|null $managerRemarks15
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks15($managerRemarks15 = null)
    {
        $this->managerRemarks15 = $managerRemarks15;

        return $this;
    }

    /**
     * Get managerRemarks15.
     *
     * @return string|null
     */
    public function getManagerRemarks15()
    {
        return $this->managerRemarks15;
    }

    /**
     * Set managerRemarks16.
     *
     * @param string|null $managerRemarks16
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks16($managerRemarks16 = null)
    {
        $this->managerRemarks16 = $managerRemarks16;

        return $this;
    }

    /**
     * Get managerRemarks16.
     *
     * @return string|null
     */
    public function getManagerRemarks16()
    {
        return $this->managerRemarks16;
    }

    /**
     * Set managerRemarks17.
     *
     * @param string|null $managerRemarks17
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks17($managerRemarks17 = null)
    {
        $this->managerRemarks17 = $managerRemarks17;

        return $this;
    }

    /**
     * Get managerRemarks17.
     *
     * @return string|null
     */
    public function getManagerRemarks17()
    {
        return $this->managerRemarks17;
    }

    /**
     * Set managerRemarks18.
     *
     * @param string|null $managerRemarks18
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks18($managerRemarks18 = null)
    {
        $this->managerRemarks18 = $managerRemarks18;

        return $this;
    }

    /**
     * Get managerRemarks18.
     *
     * @return string|null
     */
    public function getManagerRemarks18()
    {
        return $this->managerRemarks18;
    }

    /**
     * Set managerRemarks19.
     *
     * @param string|null $managerRemarks19
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks19($managerRemarks19 = null)
    {
        $this->managerRemarks19 = $managerRemarks19;

        return $this;
    }

    /**
     * Get managerRemarks19.
     *
     * @return string|null
     */
    public function getManagerRemarks19()
    {
        return $this->managerRemarks19;
    }

    /**
     * Set managerRemarks20.
     *
     * @param string|null $managerRemarks20
     *
     * @return AppraiseeResponse
     */
    public function setManagerRemarks20($managerRemarks20 = null)
    {
        $this->managerRemarks20 = $managerRemarks20;

        return $this;
    }

    /**
     * Get managerRemarks20.
     *
     * @return string|null
     */
    public function getManagerRemarks20()
    {
        return $this->managerRemarks20;
    }

    /**
     * Set appraiseeResponseComplete.
     *
     * @param string|null $appraiseeResponseComplete
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponseComplete($appraiseeResponseComplete = null)
    {
        $this->appraiseeResponseComplete = $appraiseeResponseComplete;

        return $this;
    }

    /**
     * Get appraiseeResponseComplete.
     *
     * @return string|null
     */
    public function getAppraiseeResponseComplete()
    {
        return $this->appraiseeResponseComplete;
    }

    /**
     * Set managerEvaluationComplete.
     *
     * @param string|null $managerEvaluationComplete
     *
     * @return AppraiseeResponse
     */
    public function setManagerEvaluationComplete($managerEvaluationComplete = null)
    {
        $this->managerEvaluationComplete = $managerEvaluationComplete;

        return $this;
    }

    /**
     * Get managerEvaluationComplete.
     *
     * @return string|null
     */
    public function getManagerEvaluationComplete()
    {
        return $this->managerEvaluationComplete;
    }

    /**
     * Set reviewerResponseComplete.
     *
     * @param string|null $reviewerResponseComplete
     *
     * @return AppraiseeResponse
     */
    public function setReviewerResponseComplete($reviewerResponseComplete = null)
    {
        $this->reviewerResponseComplete = $reviewerResponseComplete;

        return $this;
    }

    /**
     * Get reviewerResponseComplete.
     *
     * @return string|null
     */
    public function getReviewerResponseComplete()
    {
        return $this->reviewerResponseComplete;
    }

    /**
     * Set appraiseeResponse1.
     *
     * @param string|null $appraiseeResponse1
     *
     * @return AppraiseeResponse
     */
    public function setAppraiseeResponse1($appraiseeResponse1 = null)
    {
        $this->appraiseeResponse1 = $appraiseeResponse1;

        return $this;
    }

    /**
     * Get appraiseeResponse1.
     *
     * @return string|null
     */
    public function getAppraiseeResponse1()
    {
        return $this->appraiseeResponse1;
    }

    /**
     * Set reviewerRemarks.
     *
     * @param string|null $reviewerRemarks
     *
     * @return AppraiseeResponse
     */
    public function setReviewerRemarks($reviewerRemarks = null)
    {
        $this->reviewerRemarks = $reviewerRemarks;

        return $this;
    }

    /**
     * Get reviewerRemarks.
     *
     * @return string|null
     */
    public function getReviewerRemarks()
    {
        return $this->reviewerRemarks;
    }

    /**
     * Set goalsheetCreatedBy.
     *
     * @param string|null $goalsheetCreatedBy
     *
     * @return AppraiseeResponse
     */
    public function setGoalsheetCreatedBy($goalsheetCreatedBy = null)
    {
        $this->goalsheetCreatedBy = $goalsheetCreatedBy;

        return $this;
    }

    /**
     * Get goalsheetCreatedBy.
     *
     * @return string|null
     */
    public function getGoalsheetCreatedBy()
    {
        return $this->goalsheetCreatedBy;
    }

    /**
     * Set goalsheetCreatedOn.
     *
     * @param \DateTime|null $goalsheetCreatedOn
     *
     * @return AppraiseeResponse
     */
    public function setGoalsheetCreatedOn($goalsheetCreatedOn = null)
    {
        $this->goalsheetCreatedOn = $goalsheetCreatedOn;

        return $this;
    }

    /**
     * Get goalsheetCreatedOn.
     *
     * @return \DateTime|null
     */
    public function getGoalsheetCreatedOn()
    {
        return $this->goalsheetCreatedOn;
    }

    /**
     * Set goalsheetSubmittedBy.
     *
     * @param string|null $goalsheetSubmittedBy
     *
     * @return AppraiseeResponse
     */
    public function setGoalsheetSubmittedBy($goalsheetSubmittedBy = null)
    {
        $this->goalsheetSubmittedBy = $goalsheetSubmittedBy;

        return $this;
    }

    /**
     * Get goalsheetSubmittedBy.
     *
     * @return string|null
     */
    public function getGoalsheetSubmittedBy()
    {
        return $this->goalsheetSubmittedBy;
    }

    /**
     * Set goalsheetSubmittedOn.
     *
     * @param \DateTime|null $goalsheetSubmittedOn
     *
     * @return AppraiseeResponse
     */
    public function setGoalsheetSubmittedOn($goalsheetSubmittedOn = null)
    {
        $this->goalsheetSubmittedOn = $goalsheetSubmittedOn;

        return $this;
    }

    /**
     * Get goalsheetSubmittedOn.
     *
     * @return \DateTime|null
     */
    public function getGoalsheetSubmittedOn()
    {
        return $this->goalsheetSubmittedOn;
    }

    /**
     * Set goalsheetEvaluatedBy.
     *
     * @param string|null $goalsheetEvaluatedBy
     *
     * @return AppraiseeResponse
     */
    public function setGoalsheetEvaluatedBy($goalsheetEvaluatedBy = null)
    {
        $this->goalsheetEvaluatedBy = $goalsheetEvaluatedBy;

        return $this;
    }

    /**
     * Get goalsheetEvaluatedBy.
     *
     * @return string|null
     */
    public function getGoalsheetEvaluatedBy()
    {
        return $this->goalsheetEvaluatedBy;
    }

    /**
     * Set goalsheetEvaluatedOn.
     *
     * @param \DateTime|null $goalsheetEvaluatedOn
     *
     * @return AppraiseeResponse
     */
    public function setGoalsheetEvaluatedOn($goalsheetEvaluatedOn = null)
    {
        $this->goalsheetEvaluatedOn = $goalsheetEvaluatedOn;

        return $this;
    }

    /**
     * Get goalsheetEvaluatedOn.
     *
     * @return \DateTime|null
     */
    public function getGoalsheetEvaluatedOn()
    {
        return $this->goalsheetEvaluatedOn;
    }

    /**
     * Set goalsheetReviewedBy.
     *
     * @param string|null $goalsheetReviewedBy
     *
     * @return AppraiseeResponse
     */
    public function setGoalsheetReviewedBy($goalsheetReviewedBy = null)
    {
        $this->goalsheetReviewedBy = $goalsheetReviewedBy;

        return $this;
    }

    /**
     * Get goalsheetReviewedBy.
     *
     * @return string|null
     */
    public function getGoalsheetReviewedBy()
    {
        return $this->goalsheetReviewedBy;
    }

    /**
     * Set goalsheetReviewedOn.
     *
     * @param \DateTime|null $goalsheetReviewedOn
     *
     * @return AppraiseeResponse
     */
    public function setGoalsheetReviewedOn($goalsheetReviewedOn = null)
    {
        $this->goalsheetReviewedOn = $goalsheetReviewedOn;

        return $this;
    }

    /**
     * Get goalsheetReviewedOn.
     *
     * @return \DateTime|null
     */
    public function getGoalsheetReviewedOn()
    {
        return $this->goalsheetReviewedOn;
    }

    /**
     * Set ipf.
     *
     * @param string|null $ipf
     *
     * @return AppraiseeResponse
     */
    public function setIpf($ipf = null)
    {
        $this->ipf = $ipf;

        return $this;
    }

    /**
     * Get ipf.
     *
     * @return string|null
     */
    public function getIpf()
    {
        return $this->ipf;
    }

    /**
     * Set ipfResponse.
     *
     * @param string|null $ipfResponse
     *
     * @return AppraiseeResponse
     */
    public function setIpfResponse($ipfResponse = null)
    {
        $this->ipfResponse = $ipfResponse;

        return $this;
    }

    /**
     * Get ipfResponse.
     *
     * @return string|null
     */
    public function getIpfResponse()
    {
        return $this->ipfResponse;
    }

    /**
     * Set ipfResponseBy.
     *
     * @param string|null $ipfResponseBy
     *
     * @return AppraiseeResponse
     */
    public function setIpfResponseBy($ipfResponseBy = null)
    {
        $this->ipfResponseBy = $ipfResponseBy;

        return $this;
    }

    /**
     * Get ipfResponseBy.
     *
     * @return string|null
     */
    public function getIpfResponseBy()
    {
        return $this->ipfResponseBy;
    }

    /**
     * Set ipfResponseOn.
     *
     * @param \DateTime|null $ipfResponseOn
     *
     * @return AppraiseeResponse
     */
    public function setIpfResponseOn($ipfResponseOn = null)
    {
        $this->ipfResponseOn = $ipfResponseOn;

        return $this;
    }

    /**
     * Get ipfResponseOn.
     *
     * @return \DateTime|null
     */
    public function getIpfResponseOn()
    {
        return $this->ipfResponseOn;
    }

    /**
     * Set ipfProcessClosedOn.
     *
     * @param \DateTime|null $ipfProcessClosedOn
     *
     * @return AppraiseeResponse
     */
    public function setIpfProcessClosedOn($ipfProcessClosedOn = null)
    {
        $this->ipfProcessClosedOn = $ipfProcessClosedOn;

        return $this;
    }

    /**
     * Get ipfProcessClosedOn.
     *
     * @return \DateTime|null
     */
    public function getIpfProcessClosedOn()
    {
        return $this->ipfProcessClosedOn;
    }

    /**
     * Set ipfProcessClosedBy.
     *
     * @param string|null $ipfProcessClosedBy
     *
     * @return AppraiseeResponse
     */
    public function setIpfProcessClosedBy($ipfProcessClosedBy = null)
    {
        $this->ipfProcessClosedBy = $ipfProcessClosedBy;

        return $this;
    }

    /**
     * Get ipfProcessClosedBy.
     *
     * @return string|null
     */
    public function getIpfProcessClosedBy()
    {
        return $this->ipfProcessClosedBy;
    }

    /**
     * Set unlockRequest.
     *
     * @param string|null $unlockRequest
     *
     * @return AppraiseeResponse
     */
    public function setUnlockRequest($unlockRequest = null)
    {
        $this->unlockRequest = $unlockRequest;

        return $this;
    }

    /**
     * Get unlockRequest.
     *
     * @return string|null
     */
    public function getUnlockRequest()
    {
        return $this->unlockRequest;
    }

    /**
     * Set managerUnlocked.
     *
     * @param string|null $managerUnlocked
     *
     * @return AppraiseeResponse
     */
    public function setManagerUnlocked($managerUnlocked = null)
    {
        $this->managerUnlocked = $managerUnlocked;

        return $this;
    }

    /**
     * Get managerUnlocked.
     *
     * @return string|null
     */
    public function getManagerUnlocked()
    {
        return $this->managerUnlocked;
    }
}
