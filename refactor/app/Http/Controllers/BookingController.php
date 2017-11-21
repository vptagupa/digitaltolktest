<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {   
        if ($this->superAdmin())  $response = $this->repository->getAll($request);
        
        $response = $this->repository->getUsersJobs($request->get('user_id'));            
        
        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        return response($this->repository->with('translatorJobRel.user')->find($id));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        return response(
                $this->repository->store(
                        $request->__authenticatedUser,
                        $request->all()
                    )
            );

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $response = $this->repository->updateJob(
                            $id, 
                            array_except($request->all(), ['_token', 'submit']), 
                            $request->__authenticatedUser
                        );

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        return response(
                    $this->repository->storeJobEmail($request->all())
                );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($user_id = $request->get('user_id')) {
            return response(
                    $this->repository->getUsersJobsHistory($user_id, $request)
                );
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        return response(
                $this->repository->acceptJob($request->all(), $request->__authenticatedUser)
            );
    }

    public function acceptJobWithId(Request $request)
    {
        return response(
                $this->repository->acceptJobWithId($request->get('job_id'), $request->__authenticatedUser)
            );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        return response(
                $this->repository->cancelJobAjax($request->all(), $request->__authenticatedUser)
            );
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        return response(
                $this->repository->endJob($request->all())
            );

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function customerNotCall(Request $request)
    {
        return response(
                 $this->repository->customerNotCall($request->all())
            );

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        return response(
                $this->repository->getPotentialJobs($request->__authenticatedUser)
            );
    }

    /**
     * @param Request $request
     * @return string
     */
    public function distanceFeed(Request $request)
    {
        $data = $request->all();

        $distance = ""; 
        $time = ""; 
        $session = ""; 
        $admincomment = ""; 
        $flagged = 'no';
        $manually_handled = 'no'; 
        $by_admin = 'no';

        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        }

        if (isset($data['distance']) && $data['distance'] != "")  $distance = $data['distance'];

        if (isset($data['time']) && $data['time'] != "")  $time = $data['time'];

        if (isset($data['jobid']) && $data['jobid'] != "")  $jobid = $data['jobid'];

        if (isset($data['session_time']) && $data['session_time'] != "")  $session = $data['session_time'];

        if (isset($data['manually_handled']) && $data['manually_handled'] == 'true') $manually_handled = 'yes';

        if (isset($data['by_admin']) && $data['by_admin'] == 'true') $by_admin = 'yes';

        if (isset($data['admincomment']) && $data['admincomment'] != "") $admincomment = $data['admincomment'];

        if ($time || $distance) {

            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {

            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));

        }

        return response('Record updated!');
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function reopen(Request $request)
    {
        return response(
                $this->repository->reopen($request->all())
            );
    }

    /**
     * @param Request $request
     * @return array
     */
    public function resendNotifications(Request $request)
    {
        $this->repository->sendNotificationTranslator(
                $this->repository->find($request->get('jobid')), 
                $this->repository->jobToData($job),
                '*'
            );

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        try {
            $this->repository->sendSMSNotificationToTranslator(
                    $this->repository->find($request->get('jobid'))
                );
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
