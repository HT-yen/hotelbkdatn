<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\AddReservationRequest;
use App\Model\Guest;
use App\Model\Reservation;
use App\Model\Room;
use App\Model\Payment as PaymentModel;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\PaymentExecution;
use Illuminate\Support\Facades\Input;

class ReservationController extends Controller
{
    private $_api_payment_context;
    public function __construct() {
        $paypal_conf = \Config::get('paypal');
        $this->_api_payment_context = new ApiContext(new OAuthTokenCredential(
            $paypal_conf['client_id'],
            $paypal_conf['secret'])
        );
        $this->_api_payment_context->setConfig($paypal_conf['settings']);
    }

    /**
     * Display form for fill in booking.
     *
     * @param Room $room of rooms
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Room $room)
    {
        $bookingInfomation = Cookie::get(User::COOKIE_KEY, User::DEFAULT_VALUE);
        $emptyRooms = $room->total;

        if (isset($bookingInfomation)) {
            $checkinDate = Carbon::createFromFormat(config('hotel.datetime_format'), $bookingInfomation['checkin'] . config('hotel.checkin_time'))
                    ->toDateTimeString();
            $emptyRooms = totalEmptyRoom($room->id, $checkinDate);
        }

        return view('frontend.booking.index', compact('bookingInfomation', 'room', 'emptyRooms'));
    }

    /**
     * Save creating reservation
     *
     * @param AddReservationRequest $request Request create
     *
     * @return \Illuminate\Http\Response
     */
    public function store(AddReservationRequest $request)
    {
        \DB::beginTransaction();

        try {
            $reservation = new Reservation($request->all());

            $checkinDate = Carbon::createFromFormat(config('hotel.datetime_format'), $request->checkin . config('hotel.checkin_time'))
                    ->toDateTimeString();
            $checkoutDate = Carbon::createFromFormat(config('hotel.datetime_format'), $request->checkin . config('hotel.checkout_time'))
                    ->addDay($request->duration)
                    ->toDateTimeString();

            // set date for reservation
            $reservation->checkin_date = $checkinDate;
            $reservation->checkout_date = $checkoutDate;
            // get quanlity empty room
            $emptyRooms = totalEmptyRoom($request->room_id, $checkinDate);
            // return fail when room not enough
            if ($reservation->quantity > $emptyRooms) {
                flash(__('Sorry! The room is not enough!'))->error();
                return redirect()->back()->withInput();
            }
            // save booking infomation
            if ($reservation->target == Reservation::TARGET_USER) {
                $reservation->save();
            } else {
                $guest = Guest::where('email', $request->email)->first();
                if (!$guest) {
                    $guest = new Guest($request->all());
                    $guest->save();
                }
                $reservation->target_id = $guest->id;
                $reservation->save();
            }
            // print_r($reservation->quantity * $request->duration * $reservation->room->price);
            // print_r($reservation->room->name);

            if (count($request->query()) != 0) {
                if ($request->query()['payment'] == 'online') {

                    //payment online
                    $payer = new Payer();
                    $payer->setPaymentMethod('paypal');

                    $item_1 = new Item();
                    $item_1->setName($reservation->room->name) /** item name **/
                            ->setCurrency('USD')
                            ->setQuantity($reservation->quantity * $request->duration)
                            ->setPrice($reservation->room->price); /** unit price **/

                    $item_list = new ItemList();
                    $item_list->setItems(array($item_1));
                    $amount = new Amount();
                    $amount->setCurrency('USD')
                            ->setTotal($reservation->quantity * $request->duration * $reservation->room->price);


                    $transaction = new Transaction();
                    $transaction->setAmount($amount)
                                ->setItemList($item_list)
                                ->setDescription('Payment transaction description');

                    $redirect_urls = new RedirectUrls();
                            $redirect_urls->setReturnUrl(\URL::route('payment.status')) /** Specify return URL **/
                                ->setCancelUrl(\URL::route('payment.status'));
                    $payment = new Payment();
                    $payment->setIntent('Sale')
                        ->setPayer($payer)
                        ->setRedirectUrls($redirect_urls)
                        ->setTransactions(array($transaction));
                            /** dd($payment->create($this->_api_payment_context));exit; **/
                    try {

                        $payment->create($this->_api_payment_context);

                    } catch (\PayPal\Exception\PPConnectionException $ex) {
                        \DB::rollback();
                        if (\Config::get('app.debug')) {
                            flash(__('Connection timeout'))->error();
                        } else {
                            flash(__('Some error occur, sorry for inconvenient'))->error();
                        }
                        return redirect()->back()->withInput();
                    }

                    foreach ($payment->getLinks() as $link) {
                        if ($link->getRel() == 'approval_url') {
                            $redirect_url = $link->getHref();
                            break;
                        }
                    }
                    /** add payment ID to session **/
                    \Session::put('paypal_payment_id', $payment->getId());
                    if (isset($redirect_url)) {
                    /** redirect to paypal **/
                        // save into database
                        $paymentModel = new PaymentModel();
                        $paymentModel->reservation_id = $reservation->id;
                        $paymentModel->transaction_id = $payment->getId();
                        $paymentModel->payment_gross = $reservation->quantity * $request->duration * $reservation->room->price;
                        $paymentModel->save();
                        $reservation->status = Reservation::STATUS_ACCEPTED;
                        $reservation->save();
                        $reservation->delete();
                        \DB::commit();
                        \Session::put('paypal_room_id', $reservation->room_id);
                        \Session::put('paymentModel_id', $paymentModel->id);
                        \Session::put('paypal_reservation_id', $reservation->id);
                        return redirect()->away($redirect_url);
                    }

                    flash(__('Unknown error occurred'))->error();
                    return redirect()->back()->withInput();
                    
                }
            }
            if ($reservation->quantity >= 5 || $request->duration >= 5) {
                flash(__('Sorry! The quantity of rooms or the duration you want to book is pretty many, please to payment online'))->error();
                return redirect()->back()->withInput();

            }
            \DB::commit();
            return redirect()->back()->with('msg', __('Booking success! Thank you!'));
            
        } catch (\Exception $e) {
            dd($e);
            \DB::rollback();
            flash(__('Booking failure! Sorry'))->error();
            return redirect()->back()->withInput();
        }
    }


    public function getPaymentStatus()
    {
        try {

            /** Get the payment ID before session clear **/
            $payment_id = \Session::get('paypal_payment_id');
            $room_id = \Session::get('paypal_room_id');
            $reservation_id = \Session::get('paypal_reservation_id');
            $paymentModel_id = \Session::get('paymentModel_id');
            /** clear the session payment ID **/
            \Session::forget('paypal_payment_id');
            \Session::forget('paymentModel_id');
            \Session::forget('paypal_room_id');
            \Session::forget('paypal_reservation_id');

            if (empty(Input::get('PayerID')) || empty(Input::get('token'))) {

                flash(__('Payment failure! Sorry'))->error();
                return redirect()->route('reservations.create', $room_id);
            }
            $payment = Payment::get($payment_id, $this->_api_payment_context);
            $execution = new PaymentExecution();
            $execution->setPayerId(Input::get('PayerID'));
            /**Execute the payment **/
            $result = $payment->execute($execution, $this->_api_payment_context);
            if ($result->getState() == 'approved') {
                Reservation::withTrashed()->find($reservation_id)->restore();
                PaymentModel::withTrashed()->find($paymentModel_id)->restore();
                return redirect()->route('reservations.create', $room_id)->with('msg', __('Payment and booking success! Thank you!'));
            }
            
            flash(__('Payment failure! Sorry'))->error();
            return redirect()->route('reservations.create', $room_id);
        } catch (Exception $ex) {
            // dd($ex);

            flash(__('Payment failure! Sorry'))->error();
            return redirect()->route('reservations.create', $room_id);
        }
    }


    /**
     * Display a page update a booking room
     *
     * @param \Illuminate\Http\Request $request of user.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $reservationId = $request->route('reservation');
        $columns = [
            'id',
            'status',
            'room_id',
            'target',
            'target_id',
            'checkin_date',
            'checkout_date',
            'quantity',
            'request'
        ];
        $with['room'] = function ($query) {
            $query->select('rooms.id', 'rooms.name', 'rooms.hotel_id');
        };
        $with['reservable'] = function ($query) {
            $query->select('full_name', 'phone', 'email');
        };
        $with['room.hotel'] = function ($query) {
            $query->select('hotels.id', 'hotels.name');
        };
        $reservation = Reservation::select($columns)->with($with)->findOrFail($reservationId);
        return view('frontend.users.editHistory', compact('reservation'));
    }

    /**
     * Cancel a reservation of user.
     *
     * @param int $id            id of user.
     * @param int $reservationId id of reservation.
     *
     * @return \Illuminate\Http\Response
     */
    public function update($id, $reservationId)
    {
        $reservation = Reservation::findOrFail($reservationId)->update(['status' => Reservation::STATUS_CANCELED]);
        if ($reservation) {
            flash(__('This booking room was canceled!'))->success();
        } else {
            flash(__('Error when cancel this booking room!'))->error();
        }
        return redirect()->route('user.showBooking', [$id, $reservationId]);
    }
}
