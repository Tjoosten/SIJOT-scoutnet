<?php

namespace Sijot\Http\Controllers;

use Sijot\Http\Requests\LeaseValidator;
use Sijot\Repositories\{LeaseAdminRepository, LeaseRepository, UsersRepository};
use Sijot\Mail\{LeaseInfoRequester, LeaseInfoAdmin};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response as Status;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class LeaseController
 *
 * @package Sijot\Http\Controllers
 */
class LeaseController extends Controller
{
    // TODO: Complete the class docblock. 
    
    private $leaseDB;
    private $userDB;
    private $adminLease;

    /**
     * LeaseController constructor
     *
     * @param LeaseRepository       $leaseDB        The lease database model.
     * @param UsersRepository       $userDB         The user database model.
     * @param LeaseAdminRepository  $adminLease     The lease admin repository.
     * 
     * @return void
     */
    public function __construct(LeaseRepository $leaseDB, UsersRepository $userDB, LeaseAdminRepository $adminLease)
    {
        $routes = ['backend', 'status'];

        $this->middleware('auth')->only($routes);
        $this->middleware('forbid-banned-user')->only($routes);

        $this->leaseDB    = $leaseDB;
        $this->userDB     = $userDB;
        $this->adminLease = $adminLease;
    }

    /**
     * Get the index vor the domain lease.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $data['title'] = trans('lease.title-front-index');
        return view('lease.index', $data);
    }

    /**
     * Get the lease calendar view.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function calendar()
    {
        //! Optimize the database query. And select only the used database tables. Reduces loading time. 

        $data['title']  = trans('lease.title-front-calendar');
        $data['leases'] = $this->leaseDB->where('status_id', 3)->orderBy('start_datum', 'ASC')->paginate(15);

        return view('lease.calendar', $data);
    }

    /**
     * Get the front-end view for a domain lease request. 
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function leaseRequest()
    {
        $data['title'] = trans('lease.title-front-lease-request');
        return view('lease.request', $data);
    }

    /**
     * Store the lease request in the db.
     *
     * @param LeaseValidator $input The user input validator.
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LeaseValidator $input)
    {
        if ($this->leaseDB->create($input->except('_token'))) { // The rental has been inserted.
            if (auth()->check()) { // Requester is logged in
                flash(trans('lease.flash-lease-insert-auth'));
            } else { // Requester is not logged in.
                $when = Carbon::now()->addMinutes(15); // Needed to look your queued email.
                Mail::to($input->contact_email)->send(new LeaseInfoRequester($input->all()));

                // Start mailing to Admins and persons responsible for leases. 
                $adminUsers = $this->userDB->role('admin')->get();
                $leaseUsers = $this->userDB->role('verhuur')->get();

                foreach ($adminUsers as $admin) { // Send email notification to all the admins. 
                    Mail::to($admin->email)->send(new LeaseInfoAdmin($input->all()));
                }

                foreach ($leaseUsers as $lease) { // Set email to all persons responsibel for domain leases.
                    Mail::to($lease->email)->send(new LeaseInfoAdmin($input->all()));
                }

                // Set flash session output.
                flash(trans('lease.flash-lease-insert-no-auth'));
            }
        }

        return back(Status::HTTP_FOUND);
    }

    /**
     * Get the domain access page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function domainAccess()
    {
        $data['title'] = trans('lease.title-front-domain-access');
        return view('lease.access', $data);
    }

    /**
     * Change the lease status in the database.
     *
     * @param string $status The new lease status.
     * @param integer $leaseId The database id for the lease
     *
     * @return mixed
     */
    public function status($status, $leaseId)
    {
        try { // Check if the record exists.
            $lease = $this->leaseDB->findOrFail($leaseId);

            switch ($status) { // Check which status we need to determine.
                case 'nieuwe':    $status = 1; break; // Status = 'Nieuwe verhuur'
                case 'optie':     $status = 2; break; // Status = 'Optie'
                case 'bevestigd': $status = 3; break; // Status = 'Bevestigd'
            }

            if ($lease->update(['status_id' => $status])) {
                flash(trans('lease.flash-lease-status-change'));
            }

            return back(Status::HTTP_FOUND);
        } catch (ModelNotFoundException $exception) { // The record doesn't exists
             return app()->abort(Status::HTTP_NOT_FOUND);
        }
    }

    /**
     * Remove a lease in the database.
     *
     * @param integer $leaseId The databaseid for the lease.
     * 
     * @return mixed
     */
    public function delete($leaseId)
    {
        try { // Check if the record exists
            if ($lease = $this->leaseDB->findOrFail($leaseId)->delete()) { // The lease has been deleted.
                $lease->notitions()->sync([]);
                flash(trans('lease.flash-lease-delete'));
            }

            return back(302);
        } catch (ModelNotFoundException $exception) { // The record doesn't exists.
            return app()->abort(404);
        }
    }

    /**
     * Get the lease management view.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function backend()
    {
        $data['title']  = 'Verhuur beheer';
        $data['leases'] = $this->leaseDB->orderBy('start_datum', 'ASC')->paginate(15);
        $data['admins'] = $this->adminLease->with('person')->get();
        $data['users']  = $this->userDB->doesnthave('leaseAdmin')->select('id', 'name')->get();

        return view('lease.lease-backend', $data);
    }

    /**
     * Export the domain leases to a excel file.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function export()
    {
        //? The output needs to be styled. 
        // TODO: Row color based on status. 
        // TODO: Cell color if the date is for the own group.

        Excel::create('Verhuringen', function ($excel) {
            $excel->sheet('Verhuringen', function ($sheet) {
                $all = $this->leaseDB->orderBy('start_datum', 'ASC')->get();
                $sheet->loadView('lease.export', compact('all'));
            });
        })->export('xls');
    }
}
