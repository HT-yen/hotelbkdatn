<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Hotel;
use App\Model\Place;
use App\Model\Service;
use App\Model\Image;
use App\Model\HotelService;
use App\Http\Requests\Backend\HotelCreateRequest;
use App\Http\Requests\Backend\HotelUpdateRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Model\User;


class HotelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $columns = [
            'hotels.id',
            'hotels.name',
            'address',
            'star',
            'place_id',
            'hotels.created_at'
        ];
        $query = Hotel::search()
            ->select($columns);
        if (Auth::user()->is_admin ==User::ROLE_HOTELIER) {
            $query =$query->where('user_id', Auth::user()->id);
        }

        $hotels = $query
            ->orderby('hotels.id', 'DESC')
            ->distinct()
            ->paginate(Hotel::ROW_LIMIT)
            ->appends(['search' => request('search')]);

        return view('backend.hotels.index', compact('hotels'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $columns = [
            'id',
            'name'
        ];
        $places = Place::select($columns)->get();
        $services = Service::select($columns)->get();

        return view('backend.hotels.create', compact('places', 'services'));
    }

    /**
     * Save creating hotel
     *
     * @param HotelCreateRequest $request Request create
     *
     * @return \Illuminate\Http\Response
     */
    public function store(HotelCreateRequest $request)
    {
        \DB::beginTransaction();

        try {
            // create hotel.
            $hotel = new Hotel($request->except(['services', 'images']));
            $hotel->user_id = Auth::user()->id;
            $result = $hotel->save();
            

            //make data hotel services
            $hotelServices = array();
            if (isset($request->services)) {
                foreach ($request->services as $serviceId) {
                    array_push($hotelServices, new HotelService(['service_id' => $serviceId]));
                }
            }
            //save hotel services
            $hotel->hotelServices()->saveMany($hotelServices);

            Image::storeImages($request->images, 'hotel', $hotel->id, config('image.hotels.path_upload'));
        } catch (\Exception $e) {
            \DB::rollback();
            flash(__('Create failure'))->error();
            return redirect()->back()->withInput();
        }
        \DB::commit();
        flash(__('Create success'))->success();
        return redirect()->route('hotel.index');

    }

    /**
     * Show hotel
     *
     * @param int $id id of hotel
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $columns = [
                'id',
                'name',
                'address',
                'star',
                'introduce',
                'place_id'
            ];

            $with['place'] = function ($query) {
                $query->select('id', 'name');
            };
            $with['images'] = function ($query) {
                $query->select();
            };
            $with['hotelServices'] = function ($query) {
                $query->select('id', 'hotel_id', 'service_id');
            };
            $with['hotelServices.service'] = function ($query) {
                $query->select('id', 'name');
            };

            $hotel = Hotel::select($columns)->with($with)->findOrFail($id);
            $totalRooms = $hotel->rooms()->count();

            return view('backend.hotels.show', compact('hotel', 'totalRooms'));
        } catch(\Exception $e) {
            return "Internal server error";
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id id of hotel
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $hotel = Hotel::findOrFail($id);
        try {
            if ($hotel->delete()) {
                flash(__('Deletion successful!'))->success();
            }
            return redirect()->route('hotel.index');

        } catch(\Exception $e) {
            flash(__('Deletion failed!'))->error();
            return redirect()->route('hotel.index');
        }
    }

    /**
     * Display form edit a Hotel.
     *
     * @param int $id of Hotel
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $columns = [
                'id',
                'name',
                'address',
                'star',
                'introduce',
                'place_id'
            ];

            $with['place'] = function ($query) {
                $query->select('id', 'name');
            };
            $with['images'] = function ($query) {
                $query->select();
            };
            $with['hotelServices'] = function ($query) {
                $query->select('id', 'hotel_id', 'service_id');
            };

            $hotel = Hotel::select($columns)->with($with)->findOrFail($id);
            
            $columns = [
                'id',
                'name'
            ];
            $places = Place::select($columns)->get();
            $services = Service::select($columns)->get();
            return view('backend.hotels.edit', compact('hotel', 'places', 'services'));
     
        } catch(\Exception $e) {
            return "Internal server error";
        }

        
    }

    /**
     * Update information of a Hotel
     *
     * @param \App\Http\Requests\HotelUpdateRequest $request of form Edit Hotel
     * @param int                                   $id      of Hotel
     *
     * @return \Illuminate\Http\Response
     */
    public function update(HotelUpdateRequest $request, $id)
    {

        \DB::beginTransaction();
        try {
            $hotel = Hotel::findOrFail($id);

            $hotel->update($request->except(['services', 'images', 'user_id']));
            //delete old hotel's services
            $hotel->services()->detach();
            
            //make data hotel services
            $hotelServices = array();
            if (isset($request->services)) {
                foreach ($request->services as $serviceId) {
                    array_push($hotelServices, new HotelService(['service_id' => $serviceId]));
                }
            }
            //save hotel services
            $hotel->hotelServices()->saveMany($hotelServices);

            if (isset($request->images)) {
                Image::storeImages($request->images, 'hotel', $hotel->id, config('image.hotels.path_upload'));
            }

        } catch (\Exception $e) {
            \DB::rollback();
            flash(__('Update failure'))->error();
            return redirect()->back()->withInput();
        }
        \DB::commit();
        flash(__('Update successful!'))->success();
        return redirect()->route('hotel.show', $id);
    }
}
