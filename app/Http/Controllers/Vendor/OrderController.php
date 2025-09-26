<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use App\Models\DeliveryMan;
use App\Exports\OrderExport;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\CentralLogics\OrderLogic;
use App\Exports\OrderRefundExport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Models\Food;
use App\Models\ItemCampaign;
use App\Models\OrderDetail;
use App\Models\Restaurant;
use App\Models\Zone;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Traits\PlaceNewOrder;
use Illuminate\Support\Facades\Session;
use MatanYadaev\EloquentSpatial\Objects\Point;

class OrderController extends Controller
{
    use PlaceNewOrder;
    public function list($status, Request $request)
    {
        $key = explode(' ', $request['search']);

        $data = 0;
        $restaurant = Helpers::get_restaurant_data();
        if (($restaurant->restaurant_model == 'subscription' &&  $restaurant?->restaurant_sub?->self_delivery == 1)  || ($restaurant->restaurant_model == 'commission' &&  $restaurant->self_delivery_system == 1)) {
            $data = 1;
        }

        Order::where(['checked' => 0])->where('restaurant_id', Helpers::get_restaurant_id())->update(['checked' => 1]);

        $orders = Order::with(['customer'])
            ->when($status == 'searching_for_deliverymen', function ($query) {
                return $query->SearchingForDeliveryman();
            })
            ->when($status == 'confirmed', function ($query) {
                return $query->whereIn('order_status', ['confirmed'])->whereNotNull('confirmed');
            })
            ->when($status == 'pending', function ($query) use ($data) {
                if (config('order_confirmation_model') == 'restaurant' || $data) {
                    return $query->where('order_status', 'pending');
                } else {
                    return $query->where('order_status', 'pending')->whereIn('order_type', ['take_away', 'dine_in']);
                }
            })
            ->when($status == 'cooking', function ($query) {
                return $query->where('order_status', 'processing');
            })
            ->when($status == 'accepted', function ($query) {
                return $query->where('order_status', 'accepted');
            })
            ->when($status == 'food_on_the_way', function ($query) {
                return $query->where('order_status', 'picked_up');
            })
            ->when($status == 'delivered', function ($query) {
                return $query->Delivered();
            })
            ->when($status == 'ready_for_delivery', function ($query) {
                return $query->where('order_status', 'handover');
            })
            ->when($status == 'refund_requested', function ($query) {
                return $query->Refund_requested();
            })
            ->when($status == 'refunded', function ($query) {
                return $query->Refunded();
            })
            ->when($status == 'payment_failed', function ($query) {
                return $query->where('order_status', 'failed');
            })
            ->when($status == 'canceled', function ($query) {
                return $query->where('order_status', 'canceled');
            })
            ->when($status == 'dine_in', function ($query) {
                return $query->where('order_type', 'dine_in');
            })
            // ->when($status == 'assinged', function($query){
            //     return $query->whereNotIn('order_status',['failed','canceled', 'refund_requested', 'refunded','delivered','refund_request_canceled'])->whereNotNull('delivery_man_id');
            // })

            ->when($status == 'scheduled', function ($query) use ($data) {
                return $query->Scheduled()->where(function ($q) use ($data) {
                    if (config('order_confirmation_model') == 'restaurant' || $data) {
                        $q->whereNotIn('order_status', ['failed', 'canceled', 'refund_requested', 'refunded']);
                    } else {
                        $q->whereNotIn('order_status', ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])->orWhere(function ($query) {
                            $query->where('order_status', 'pending')->whereIn('order_type', ['take_away', 'dine_in']);
                        });
                    }
                });
            })
            ->when($status == 'all', function ($query) use ($data) {
                return $query->where(function ($q1) use ($data) {
                    $q1->whereNotIn('order_status', (config('order_confirmation_model') == 'restaurant' || $data) ? ['failed', 'canceled', 'refund_requested', 'refunded'] : ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])
                        ->orWhere(function ($q2) {
                            return $q2->where('order_status', 'pending')->whereIn('order_type', ['take_away', 'dine_in']);
                        })->orWhere(function ($q3) {
                            return $q3->where('order_status', 'pending')->whereNotNull('subscription_id');
                        });
                });
            })
            ->when(in_array($status, ['pending', 'confirmed']), function ($query) {
                return $query->OrderScheduledIn(30);
            })
            ->when(isset($key), function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('id', 'like', "%{$value}%")
                            ->orWhere('order_status', 'like', "%{$value}%")
                            ->orWhere('transaction_reference', 'like', "%{$value}%");
                    }
                });
            })
            ->Notpos()
            ->NotDigitalOrder()
            ->hasSubscriptionToday()
            ->where('restaurant_id', \App\CentralLogics\Helpers::get_restaurant_id())
            ->orderBy('schedule_at', 'desc')
            ->paginate(config('default_pagination'));

        $st = $status;
        $status = translate('messages.' . $status);
        return view('vendor-views.order.list', compact('orders', 'status', 'st'));
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $orders = Order::where(['restaurant_id' => Helpers::get_restaurant_id()])->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                    ->orWhere('order_status', 'like', "%{$value}%")
                    ->orWhere('transaction_reference', 'like', "%{$value}%");
            }
        })->Notpos()
            ->NotDigitalOrder()
            ->limit(100)->get();
        return response()->json([
            'view' => view('vendor-views.order.partials._table', compact('orders'))->render()
        ]);
    }

    public function details(Request $request, $id)
    {
        $data = 0;
        $restaurant = Helpers::get_restaurant_data();
        if (($restaurant->restaurant_model == 'subscription' &&  $restaurant?->restaurant_sub?->self_delivery == 1)  || ($restaurant->restaurant_model == 'commission' &&  $restaurant->self_delivery_system == 1)) {
            $data = 1;
        }
        $status = 'all';

        $order = Order::with(['offline_payments', 'payments', 'subscription', 'subscription.schedule_today', 'details.food', 'customer' => function ($query) {
            return $query->withCount('orders');
        }, 'delivery_man' => function ($query) {
            return $query->withCount('orders');
        }])->where(['id' => $id, 'restaurant_id' => Helpers::get_restaurant_id()])

            ->Notpos()
            ->NotDigitalOrder()
            ->when($status == 'all', function ($query) use ($data) {
                return $query->where(function ($q1) use ($data) {
                    $q1->whereNotIn('order_status', (config('order_confirmation_model') == 'restaurant' || $data) ? [''] : ['pending'])
                        ->orWhere(function ($q2) {
                            return $q2->where('order_status', 'pending')->whereIn('order_type', ['take_away', 'dine_in']);
                        })->orWhere(function ($q3) {
                            return $q3->where('order_status', 'pending')->whereNotNull('subscription_id');
                        });
                });
            })
            // ->hasSubscriptionToday()
            ->first();

        if (isset($order)) {
            $deliveryMen = DeliveryMan::with('last_location')->where('restaurant_id', Helpers::get_restaurant_id())->active()->get();
            $deliveryMen = Helpers::deliverymen_list_formatting(data: $deliveryMen, restaurant_lat: $order?->restaurant?->latitude, restaurant_lng: $order?->restaurant?->longitude);

            $selected_delivery_man = DeliveryMan::with('last_location')->where('id', $order->delivery_man_id)->first() ?? [];
            if ($order->delivery_man) {
                $selected_delivery_man = Helpers::deliverymen_list_formatting(data: $selected_delivery_man, restaurant_lat: $order?->restaurant?->latitude, restaurant_lng: $order?->restaurant?->longitude, single_data: true);
            }
            if (Helpers::employee_module_permission_check('regular_order') && Helpers::employee_module_permission_check('subscription_order')) {
                return view('vendor-views.order.order-view', compact('order', 'selected_delivery_man', 'deliveryMen'));
            }
            if ((Helpers::employee_module_permission_check('regular_order') && !$order->subscription_id)) {
                return view('vendor-views.order.order-view', compact('order', 'selected_delivery_man', 'deliveryMen'));
            }
            if ((Helpers::employee_module_permission_check('subscription_order') && $order->subscription_id)) {
                return view('vendor-views.order.order-view', compact('order', 'selected_delivery_man', 'deliveryMen'));
            }
            Toastr::error('access_denied!');
            return back();
        } else {
            Toastr::info('No more orders!');
            return back();
        }
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'order_status' => 'required|in:confirmed,processing,handover,delivered,canceled',
            'reason' => 'required_if:order_status,canceled',
        ], [
            'id.required' => 'Order id is required!'
        ]);

        $order = Order::where(['id' => $request->id, 'restaurant_id' => Helpers::get_restaurant_id()])->with(['subscription_logs', 'details'])->first();

        if ($order->delivered != null) {
            Toastr::warning(translate('messages.cannot_change_status_after_delivered'));
            return back();
        }

        if ($request['order_status'] == 'canceled' && !config('canceled_by_restaurant')) {
            Toastr::warning(translate('messages.you_can_not_cancel_a_order'));
            return back();
        }

        if ($request['order_status'] == 'canceled' && $order->confirmed) {
            Toastr::warning(translate('messages.you_can_not_cancel_after_confirm'));
            return back();
        }

        $data = 0;
        $restaurant = Helpers::get_restaurant_data();
        if (($restaurant->restaurant_model == 'subscription' && $restaurant?->restaurant_sub?->self_delivery == 1)  || ($restaurant->restaurant_model == 'commission' &&  $restaurant->self_delivery_system == 1)) {
            $data = 1;
        }

        if ($request['order_status'] == 'delivered' && !in_array($order['order_type'], ['dine_in', 'take_away']) && !$data) {
            Toastr::warning(translate('messages.you_can_not_delivered_delivery_order'));
            return back();
        }

        if ($request['order_status'] == "confirmed") {
            if (!$data && config('order_confirmation_model') == 'deliveryman' && !in_array($order['order_type'], ['dine_in', 'take_away']) && $order->subscription_id == null) {
                Toastr::warning(translate('messages.order_confirmation_warning'));
                return back();
            }
        }

        if ($request->order_status == 'delivered') {
            $order_delivery_verification = (bool)\App\Models\BusinessSetting::where(['key' => 'order_delivery_verification'])->first()?->value;
            if ($order_delivery_verification) {
                if ($request->otp) {
                    if ($request->otp != $order->otp) {
                        Toastr::warning(translate('messages.order_varification_code_not_matched'));
                        return back();
                    }
                } else {
                    Toastr::warning(translate('messages.order_varification_code_is_required'));
                    return back();
                }
            }
            if (isset($order->subscription_id) && count($order->subscription_logs) == 0) {
                Toastr::warning(translate('messages.You_Can_Not_Delivered_This_Subscription_order_Before_Schedule'));
                return back();
            }

            if ($order->transaction  == null || isset($order->subscription_id)) {
                $unpaid_payment = OrderPayment::where('payment_status', 'unpaid')->where('order_id', $order->id)->first()?->payment_method;
                $unpaid_pay_method = 'digital_payment';
                if ($unpaid_payment) {
                    $unpaid_pay_method = $unpaid_payment;
                }

                if ($order->payment_method == 'cash_on_delivery' || $unpaid_pay_method == 'cash_on_delivery') {
                    $ol = OrderLogic::create_transaction(order: $order, received_by: 'restaurant', status: null);
                } else {
                    $ol = OrderLogic::create_transaction(order: $order, received_by: 'admin', status: null);
                }


                if (!$ol) {
                    Toastr::warning(translate('messages.faield_to_create_order_transaction'));
                    return back();
                }
            }

            $order->payment_status = 'paid';

            OrderLogic::update_unpaid_order_payment(order_id: $order->id, payment_method: $order->payment_method);

            $order->details->each(function ($item, $key) {
                if ($item->food) {
                    $item->food->increment('order_count');
                }
            });
            $order->customer ?  $order->customer->increment('order_count') : '';
        }
        if ($request->order_status == 'canceled' || $request->order_status == 'delivered') {
            if ($order->delivery_man) {
                $dm = $order->delivery_man;
                $dm->current_orders = $dm->current_orders > 1 ? $dm->current_orders - 1 : 0;
                $dm->save();
            }
        }

        if ($request->order_status == 'canceled') {
            Helpers::increment_order_count($order->restaurant);
            $order->cancellation_reason = $request->reason;
            $order->canceled_by = 'restaurant';
            if (!isset($order->confirmed) && isset($order->subscription_id)) {
                $order->subscription()->update(['status' => 'canceled']);
                if ($order?->subscription?->log) {
                    $order->subscription->log()->update([
                        'order_status' => $request->status,
                        'canceled' => now(),
                    ]);
                }
            }
            Helpers::decreaseSellCount(order_details: $order->details);
        }
        if ($request->order_status == 'delivered') {
            $order->restaurant->increment('order_count');
            if ($order->delivery_man) {
                $order->delivery_man->increment('order_count');
            }
        }
        $order->order_status = $request->order_status;
        if ($request->order_status == "processing") {
            $order->processing_time = $request->processing_time;
        }
        $order[$request['order_status']] = now();
        $order->save();


        if (!Helpers::send_order_notification($order)) {
            Toastr::warning(translate('messages.push_notification_faild'));
        }
        OrderLogic::update_subscription_log($order);
        Toastr::success(translate('messages.order_status_updated'));
        return back();
    }


    public function generate_invoice($id)
    {
        $order = Order::where(['id' => $id, 'restaurant_id' => Helpers::get_restaurant_id()])->with(['payments'])->first();
        if (Helpers::employee_module_permission_check('regular_order') && Helpers::employee_module_permission_check('subscription_order')) {
            return view('vendor-views.order.invoice', compact('order'));
        }
        if ((Helpers::employee_module_permission_check('regular_order') && !$order->subscription_id)) {
            return view('vendor-views.order.invoice', compact('order'));
        }
        if ((Helpers::employee_module_permission_check('subscription_order') && $order->subscription_id)) {
            return view('vendor-views.order.invoice', compact('order'));
        }
        Toastr::error('Access Denied');
        return back();
    }

    public function add_payment_ref_code(Request $request, $id)
    {
        Order::where(['id' => $id, 'restaurant_id' => Helpers::get_restaurant_id()])->update([
            'transaction_reference' => $request['transaction_reference']
        ]);

        Toastr::success('Payment reference code is added!');
        return back();
    }


    public function orders_export($status, Request $request)
    {
        try {
            $key = explode(' ', $request['search']);

            $data = 0;
            $restaurant = Helpers::get_restaurant_data();
            if (($restaurant->restaurant_model == 'subscription' &&  $restaurant?->restaurant_sub?->self_delivery == 1)  || ($restaurant->restaurant_model == 'commission' &&  $restaurant->self_delivery_system == 1)) {
                $data = 1;
            }

            Order::where(['checked' => 0])->where('restaurant_id', Helpers::get_restaurant_id())->update(['checked' => 1]);

            $orders = Order::with(['customer'])
                ->when($status == 'searching_for_deliverymen', function ($query) {
                    return $query->SearchingForDeliveryman();
                })
                ->when($status == 'confirmed', function ($query) {
                    return $query->whereIn('order_status', ['confirmed', 'accepted'])->whereNotNull('confirmed');
                })
                ->when($status == 'pending', function ($query) use ($data) {
                    if (config('order_confirmation_model') == 'restaurant' || $data) {
                        return $query->where('order_status', 'pending');
                    } else {
                        return $query->where('order_status', 'pending')->whereIn('order_type', ['take_away', 'dine_in']);
                    }
                })
                ->when($status == 'cooking', function ($query) {
                    return $query->where('order_status', 'processing');
                })
                ->when($status == 'food_on_the_way', function ($query) {
                    return $query->where('order_status', 'picked_up');
                })
                ->when($status == 'delivered', function ($query) {
                    return $query->Delivered();
                })
                ->when($status == 'ready_for_delivery', function ($query) {
                    return $query->where('order_status', 'handover');
                })
                ->when($status == 'refund_requested', function ($query) {
                    return $query->Refund_requested();
                })
                ->when($status == 'refunded', function ($query) {
                    return $query->Refunded();
                })
                ->when($status == 'dine_in', function ($query) {
                    return $query->where('order_type', 'dine_in');
                })
                ->when($status == 'scheduled', function ($query) use ($data) {
                    return $query->Scheduled()->where(function ($q) use ($data) {
                        if (config('order_confirmation_model') == 'restaurant' || $data) {
                            $q->whereNotIn('order_status', ['failed', 'canceled', 'refund_requested', 'refunded']);
                        } else {
                            $q->whereNotIn('order_status', ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])->orWhere(function ($query) {
                                $query->where('order_status', 'pending')->whereIn('order_type', ['take_away', 'dine_in']);
                            });
                        }
                    });
                })
                ->when($status == 'all', function ($query) use ($data) {
                    return $query->where(function ($q1) use ($data) {
                        $q1->whereNotIn('order_status', (config('order_confirmation_model') == 'restaurant' || $data) ? ['failed', 'canceled', 'refund_requested', 'refunded'] : ['pending', 'failed', 'canceled', 'refund_requested', 'refunded'])
                            ->orWhere(function ($q2) {
                                return $q2->where('order_status', 'pending')->whereIn('order_type', ['take_away', 'dine_in']);
                            })->orWhere(function ($q3) {
                                return $q3->where('order_status', 'pending')->whereNotNull('subscription_id');
                            });
                    });
                })
                ->when(in_array($status, ['pending', 'confirmed']), function ($query) {
                    return $query->OrderScheduledIn(30);
                })
                ->when(isset($key), function ($query) use ($key) {
                    return $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('id', 'like', "%{$value}%")
                                ->orWhere('order_status', 'like', "%{$value}%")
                                ->orWhere('transaction_reference', 'like', "%{$value}%");
                        }
                    });
                })
                ->Notpos()
                ->NotDigitalOrder()
                ->hasSubscriptionToday()
                ->where('restaurant_id', \App\CentralLogics\Helpers::get_restaurant_id())
                ->orderBy('schedule_at', 'desc')
                ->get();

            if (in_array($status, ['requested', 'rejected', 'refunded'])) {
                $data = [
                    'orders' => $orders,
                    'type' => $request->order_type ?? translate('messages.all'),
                    'status' => $status,
                    'order_status' => isset($request->orderStatus) ? implode(', ', $request->orderStatus) : null,
                    'search' => $request->search ?? $key[0] ?? null,
                    'from' => $request->from_date ?? null,
                    'to' => $request->to_date ?? null,
                    'zones' => isset($request->zone) ? Helpers::get_zones_name($request->zone) : null,
                    'restaurant' => Helpers::get_restaurant_name(Helpers::get_restaurant_id()),
                ];

                if ($request->type == 'excel') {
                    return Excel::download(new OrderRefundExport($data), 'RefundOrders.xlsx');
                } else if ($request->type == 'csv') {
                    return Excel::download(new OrderRefundExport($data), 'RefundOrders.csv');
                }
            }


            $data = [
                'orders' => $orders,
                'type' => $request->order_type ?? translate('messages.all'),
                'status' => $status,
                'order_status' => isset($request->orderStatus) ? implode(', ', $request->orderStatus) : null,
                'search' => $request->search ?? $key[0] ?? null,
                'from' => $request->from_date ?? null,
                'to' => $request->to_date ?? null,
                'zones' => isset($request->zone) ? Helpers::get_zones_name($request->zone) : null,
                'restaurant' => Helpers::get_restaurant_name(Helpers::get_restaurant_id()),
            ];

            if ($request->type == 'excel') {
                return Excel::download(new OrderExport($data), 'Orders.xlsx');
            } else if ($request->type == 'csv') {
                return Excel::download(new OrderExport($data), 'Orders.csv');
            }
        } catch (\Exception $e) {
            // dd($e);
            Toastr::error("line___{$e->getLine()}", $e->getMessage());
            info(["line___{$e->getLine()}", $e->getMessage()]);
            return back();
        }
    }

    public function add_order_proof(Request $request, $id)
    {
        $order = Order::find($id);
        $img_names = $order->order_proof ? json_decode($order->order_proof) : [];
        $images = [];
        $total_file =  (is_array($request->order_proof) ? count($request->order_proof)  : 0) + count($img_names);
        if (!$img_names) {
            $request->validate([
                'order_proof' => 'required|array|max:5',
            ]);
        }

        if ($total_file > 5) {
            Toastr::error(translate('messages.order_proof_must_not_have_more_than_5_item'));
            return back();
        }

        if (!empty($request->file('order_proof'))) {
            foreach ($request->order_proof as $img) {
                $image_name = Helpers::upload('order/', 'png', $img);
                array_push($img_names, ['img' => $image_name, 'storage' => Helpers::getDisk()]);
            }
            $images = $img_names;
        }

        $order->order_proof = json_encode($images);
        $order->save();

        Toastr::success(translate('messages.order_proof_added'));
        return back();
    }


    public function remove_proof_image(Request $request)
    {
        $order = Order::find($request['id']);
        $array = [];
        $proof = isset($order->order_proof) ? json_decode($order->order_proof, true) : [];
        if (count($proof) < 2) {
            Toastr::warning(translate('all_image_delete_warning'));
            return back();
        }
        Helpers::check_and_delete('order/', $request['image']);
        foreach ($proof as $image) {
            if ($image != $request['name']) {
                array_push($array, $image);
            }
        }
        Order::where('id', $request['id'])->update([
            'order_proof' => json_encode($array),
        ]);
        Toastr::success(translate('order_proof_image_removed_successfully'));
        return back();
    }
    public function download($file_name)
    {
        return Storage::download(base64_decode($file_name));
    }

    public function add_delivery_man($order_id, $delivery_man_id)
    {
        if ($delivery_man_id == 0) {
            return response()->json(['message' => translate('messages.deliveryman_not_found')], 404);
        }
        $order = Order::Notpos()->with(['subscription.schedule_today'])->find($order_id);
        $deliveryman = DeliveryMan::where('id', $delivery_man_id)->available()->active()->first();
        if ($order->delivery_man_id == $delivery_man_id) {
            return response()->json(['message' => translate('messages.order_already_assign_to_this_deliveryman')], 400);
        }
        if ($deliveryman) {
            if ($order->delivery_man) {
                $dm = $order->delivery_man;
                $dm->current_orders = $dm->current_orders > 1 ? $dm->current_orders - 1 : 0;
                // $dm->decrement('assigned_order_count');
                $dm->save();


                $deliveryman_push_notification_status = Helpers::getNotificationStatusData('deliveryman', 'deliveryman_order_assign_unassign');
                if ($deliveryman_push_notification_status?->push_notification_status  == 'active' && $dm->fcm_token) {

                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => translate('messages.you_are_unassigned_from_a_order'),
                        'order_id' => '',
                        'image' => '',
                        'type' => 'unassign'
                    ];
                    Helpers::send_push_notif_to_device($dm->fcm_token, $data);

                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'delivery_man_id' => $dm->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            $order->delivery_man_id = $delivery_man_id;
            $order->order_status = in_array($order->order_status, ['pending', 'confirmed']) ? 'accepted' : $order->order_status;
            $order->accepted = now();
            $order->save();
            OrderLogic::update_subscription_log($order);
            $deliveryman->current_orders = $deliveryman->current_orders + 1;
            $deliveryman->save();
            $deliveryman->increment('assigned_order_count');

            $value = Helpers::text_variable_data_format(
                value: Helpers::order_status_update_message('accepted', $order->customer ? $order?->customer?->current_language_key : 'en'),
                restaurant_name: $order->restaurant?->name,
                order_id: $order->id,
                user_name: "{$order?->customer?->f_name} {$order?->customer?->l_name}",
                delivery_man_name: "{$order?->delivery_man?->f_name} {$order?->delivery_man?->l_name}"
            );

            try {
                $customer_push_notification_status = Helpers::getNotificationStatusData('customer', 'customer_order_notification');

                if ($customer_push_notification_status?->push_notification_status  == 'active' && $value && $order?->customer?->cm_firebase_token) {
                    $fcm_token = $order->customer->cm_firebase_token;
                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                        'type' => 'order_status',
                        'order_status' => $order->order_status,
                    ];
                    Helpers::send_push_notif_to_device($fcm_token, $data);

                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'user_id' => $order->customer->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                $deliveryman_push_notification_status = Helpers::getNotificationStatusData('deliveryman', 'deliveryman_order_assign_unassign');
                if ($deliveryman_push_notification_status?->push_notification_status  == 'active' && $deliveryman->fcm_token) {
                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => translate('messages.you_are_assigned_to_a_order'),
                        'order_id' => $order['id'],
                        'image' => '',
                        'type' => 'assign'
                    ];
                    Helpers::send_push_notif_to_device($deliveryman->fcm_token, $data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'delivery_man_id' => $deliveryman->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            } catch (\Exception $e) {
                info($e->getMessage());
                Toastr::warning(translate('messages.push_notification_faild'));
            }
            return response()->json([], 200);
        }
        return response()->json(['message' => translate('Deliveryman not available!')], 400);
    }

    public function add_dine_in_table_number(Order $order, Request $request)
    {
        $request->validate([
            'table_number' => 'nullable|max:255|required_without_all:token_number',
            'token_number' => 'nullable|max:255|required_without_all:table_number',
        ], [
            'table_number.required_without_all' => translate('you_must_set_a_table_or_token_number'),
            'token_number.required_without_all' => translate('you_must_set_a_table_or_token_number'),
        ]);

        if ($order?->order_type  == 'dine_in') {
            $order->OrderReference()->update([
                'token_number' => $request->token_number ?? null,
                'table_number' => $request->table_number ?? null
            ]);
        }
        $data = [
            // 'title' => $request->table_number  ? translate('Table_number_is_added') : translate('Token_number_is_added') ,
            // 'description' =>  $request->table_number  ? translate('Table_number_is') .' '.$request->token_number :  translate('Token_number_is') .' '.$request->token_number,
            'title' =>  $request->table_number &&  $request->token_number ?  translate('Here_is_your_Table_and_Token_Number') : ($request->table_number  ? translate('Here_is_your_Table_Number') : translate('Here_is_your_Token_Number')),
            'description' => $request->table_number &&  $request->token_number ?  translate('Table No -') . ' ' . $request->table_number  . ' ' . translate('&_Token No -') . ' ' . $request->token_number : ($request->table_number  ? translate('Table No -') . ' ' . $request->table_number  :  translate('Token No -') . ' ' . $request->token_number),
            'order_id' => $order->id,
            'image' => '',
            'type' => 'order_status',
            'order_status' => $order->order_status,
        ];
        $notification_status = Helpers::getNotificationStatusData('customer', 'customer_dine_in_table_or_token');
        $fcm = ($order->is_guest == 0 ? $order?->customer?->cm_firebase_token : $order?->guest?->fcm_token) ?? null;
        if ($notification_status?->push_notification_status  == 'active' && $fcm) {
            Helpers::send_push_notif_to_device($fcm, $data);
            DB::table('user_notifications')->insert([
                'data' => json_encode($data),
                'user_id' => $order?->user_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        Toastr::success($request->table_number ? translate('table_number_updated_successfully') : translate('token_number_updated_successfully'));
        return back();
    }


    public function updateSchedule(Request $request)
    {

        $order = Order::where('id', $request->order_id)->first();
        $schedule_at = Carbon::parse($request->date);

        $restaurant = Restaurant::selectRaw('*, IF(((select count(*) from `restaurant_schedule` where `restaurants`.`id` = `restaurant_schedule`.`restaurant_id` and `restaurant_schedule`.`day` = ' . (int) $schedule_at->format('w') . ' and `restaurant_schedule`.`opening_time` < "' . $schedule_at->format('H:i:s') . '" and `restaurant_schedule`.`closing_time` >"' . $schedule_at->format('H:i:s') . '") > 0), true, false) as open')->where('id', Helpers::get_restaurant_id())->first();

        if ($restaurant->open) {
            $order->schedule_at = $schedule_at;
            $order->save();
            $data = ['status' => 'success', 'message' => translate('messages.order_has_been_scheduled')];
        } else {
            $data = ['status' => 'closed',  'message' => translate('restaurant_is_closed_on_this_time')];
        }

        $this->makeEditOrderLogs($order->id, 'edited_schedule_date_&_time', 'vendor');
        return response()->json($data, 200);
    }



    public function remove_from_cart(Request $request)
    {
        $carts = Session::get('order_edit_cart', []);

        foreach ($carts as $key => $cart) {
            if ($cart['cart_id'] == $request->cart_id) {
                if (data_get($cart, 'order_detail_id')) {
                    $edit_logs = Session::get('order_edit_logs', []);
                    $edit_logs[] = 'delete_item';
                    Session::put('order_edit_logs', $edit_logs);
                }
                unset($carts[$key]);
                break;
            }
        }
        $carts = array_values($carts);
        foreach ($carts as $index => &$cart) {
            $cart['cart_id'] = $index + 1;
        }
        Session::put('order_edit_cart', $carts);
        return response()->json([
            'view' => view('vendor-views.order.partials._food_list', compact('carts'))->render(),
        ]);
    }
    public function getSearchedFoods(Request $request)
    {
        $key = explode(' ', $request->search);

        $foods =  Food::where('restaurant_id', Helpers::get_restaurant_id())
            ->when($request->search, function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                });
            })->active()
            ->orderByRaw("FIELD(name, ?) DESC", [$request->search])
            ->latest()->paginate(10);


        return response()->json([
            'view' => view('vendor-views.order.partials._searched_food_list', compact('foods'))->render(),
        ]);
    }

    public function add_to_cart(Request $request)
    {
        $old_selected_addons = [];
        $old_selected_variations = [];
        $old_selected_without_variation = $request?->old_selected_without_variation ?? 0;

        if ($request?->old_selected_variations) {
            $old_selected_variations = json_decode($request->old_selected_variations, true) ?? [];
        }
        if ($request?->old_selected_addons) {
            $old_selected_addons = json_decode($request->old_selected_addons, true) ?? [];
        }

        if ($request->item_type == 'campaign') {
            $product = ItemCampaign::where('restaurant_id', Helpers::get_restaurant_id())->find($request->id);
        } else {
            $product = Food::where('restaurant_id', Helpers::get_restaurant_id())->with('restaurant')->find($request->id);
        }

        $variations = [];
        $price = 0;
        $addon_price = 0;
        $variation_price = 0;
        $optionIds = [];
        $carts = [];
        $add_ons = [];
        $add_on_qtys = [];
        $add_on_ids = [];

        $product_variations = json_decode($product->variations, true);
        if ($request->variations && count($product_variations)) {
            foreach ($request->variations  as  $value) {

                if ($value['required'] == 'on' &&  isset($value['values']) == false) {
                    return response()->json([
                        'data' => 'variation_error',
                        'message' => translate('Please select items from') . ' ' . $value['name'],
                    ]);
                }
                if (isset($value['values'])  && $value['min'] != 0 && $value['min'] > count($value['values']['label'])) {
                    return response()->json([
                        'data' => 'variation_error',
                        'message' => translate('Please select minimum ') . $value['min'] . translate(' For ') . $value['name'] . '.',
                    ]);
                }
                if (isset($value['values']) && $value['max'] != 0 && $value['max'] < count($value['values']['label'])) {
                    return response()->json([
                        'data' => 'variation_error',
                        'message' => translate('Please select maximum ') . $value['max'] . translate(' For ') . $value['name'] . '.',
                    ]);
                }
            }

            $variation_data = Helpers::get_varient($product_variations, $request->variations);
            $variation_price = $variation_data['price'];
            $variations = $variation_data['variations'];
            $optionIds = $variation_data['optionIds'];
        }

        if ($request['addon_id']) {
            foreach ($request['addon_id'] as $id) {
                $add_on_ids[] = $id;
                $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                $add_on_qtys[] = $request['addon-quantity' . $id];
            }
            $add_ons = $request['addon_id'];
        }

        $addonAndVariationStock = Helpers::addonAndVariationStockCheck(product: $product,  quantity: $request->quantity, add_on_qtys: $add_on_qtys, variation_options: $optionIds, add_on_ids: $add_on_ids, old_selected_variations: $old_selected_variations, old_selected_without_variation: $old_selected_without_variation, old_selected_addons: $old_selected_addons);
        if (data_get($addonAndVariationStock, 'out_of_stock') != null) {
            return response()->json([
                'data' => 'stock_out',
                'message' => data_get($addonAndVariationStock, 'out_of_stock'),
                'current_stock' => data_get($addonAndVariationStock, 'current_stock'),
                'id' => data_get($addonAndVariationStock, 'id'),
                'type' => data_get($addonAndVariationStock, 'type'),
            ], 203);
        }

        $addon_data = Helpers::calculate_addon_price(addons: AddOn::where('restaurant_id', Helpers::get_restaurant_id())->whereIn('id', $add_ons)->get(), add_on_qtys: $add_on_qtys, old_selected_addons: $old_selected_addons);

        $addon_price = $addon_data['total_add_on_price'] ?? 0;
        $price = $product->price + $variation_price;
        $price = $price - Helpers::product_discount_calculate(product: $product, price: $price, restaurant: $product->restaurant);
        $price = $price * $request->quantity + $addon_price;

        $carts = Session::get('order_edit_cart', []);

        if (!$request->cart_id) {
            $newVariations = $optionIds;
            sort($newVariations);

            $duplicateExists = false;
            foreach ($carts as $cartItem) {
                if ($cartItem['item_id'] == $product->id) {
                    $existingVariations = $cartItem['variation_options'];
                    sort($existingVariations);

                    if ($existingVariations === $newVariations) {
                        $duplicateExists = true;
                        break;
                    }
                }
            }

            if ($duplicateExists) {
                return response()->json(['data' => 1]);
            }
            $cart['cart_id'] = count($carts) + 1;
            $cart['item_type'] = 'food';
            $cart['name'] = $product->name;
            $cart['image_full_url'] = $product->image_full_url;
            $cart['item_id'] = $product->id;
            $cart['quantity'] = $request->quantity;
            $cart['price'] = $price;
            $cart['maximum_cart_quantity'] =  min($product?->maximum_cart_quantity ?? 9999999999, $product?->stock_type == 'unlimited' ? 999999999 : $product?->item_stock + $old_selected_without_variation);
            $cart['add_on_ids'] = $add_on_ids;
            $cart['add_on_qtys'] = $add_on_qtys;
            $cart['add_ons'] =  json_encode($addon_data['addons']);
            $cart['variation_options'] = $optionIds;
            $cart['addon_price'] = $addon_price;
            $cart['variations'] = $variations;
            $cart['new_item'] = true;
            $carts[] = $cart;

            $edit_logs = Session::get('order_edit_logs', []);
            $edit_logs[] = 'add_new_item';
            Session::put('order_edit_logs', $edit_logs);
            $message = translate('product_has_been_added_in_cart');
        } else {
            $index = array_search($request->cart_id, array_column($carts, 'cart_id'));
            if ($index !== false) {
                if (data_get($carts[$index], 'order_detail_id') && $request->quantity != data_get($carts[$index], 'quantity')) {
                    $edit_logs = Session::get('order_edit_logs', []);
                    $edit_logs[] = 'edited_item_quantity';
                    Session::put('order_edit_logs', $edit_logs);
                }
                $carts[$index]['item_type'] = 'food';
                $carts[$index]['name'] = $product->name;
                $carts[$index]['image_full_url'] = $product->image_full_url;
                $carts[$index]['item_id'] = $product->id;
                $carts[$index]['quantity'] = $request->quantity;
                $carts[$index]['price'] = $price;
                $carts[$index]['maximum_cart_quantity'] =  min($product?->maximum_cart_quantity ?? 9999999999, $product?->stock_type == 'unlimited' ? 999999999 : $product?->item_stock + $old_selected_without_variation);
                $carts[$index]['add_on_ids'] = $add_on_ids;
                $carts[$index]['add_on_qtys'] = $add_on_qtys;
                $carts[$index]['add_ons'] = json_encode($addon_data['addons']);
                $carts[$index]['variation_options'] = $optionIds;
                $carts[$index]['variations'] = $variations;
                $carts[$index]['addon_price'] = $addon_price;
                $message = translate('product_has_been_updated');
            }
        }


        Session::put('order_edit_cart', $carts);
        return response()->json([
            'data' => 0,
            'message' => $message,
            'view' => view('vendor-views.order.partials._food_list', compact('carts'))->render(),
        ]);
    }


    public function edit(Request $request, Order $order)
    {
        Session::forget('order_edit_cart');

        $order = Order::where('restaurant_id', Helpers::get_restaurant_id())
            ->where(['id' => $order->id])->with('details.food', 'restaurant')->first();

        $carts = [];
        foreach ($order->details ?? [] as $key => $detail) {

            if (!$detail->food) {
                return response()->json([
                    'error' => 'food_not_found'
                ]);
            }

            $optionIds = [];
            $add_on_ids = [];
            $add_on_qtys = [];
            $old_selected_variations = [];
            if ($detail->variation != '[]') {
                foreach (json_decode($detail->variation, true) as $value) {
                    foreach (data_get($value, 'values', []) as $item) {
                        if (data_get($item, 'option_id', null) != null) {
                            $optionIds[] = data_get($item, 'option_id', null);
                            $old_selected_variations[data_get($item, 'option_id')] = $detail->quantity;
                        }
                    }
                }
            }

            foreach (json_decode($detail->add_ons, true) as $add_ons) {
                if (data_get($add_ons, 'id', null) != null) {
                    $add_on_ids[] = data_get($add_ons, 'id', null);
                    $add_on_qtys[] = data_get($add_ons, 'quantity', 1);
                }
            }

            $product_variations = json_decode($detail->variation, true);
            $variations = [];
            if (count($product_variations)) {
                $variation_data = Helpers::getVariationPrice($optionIds);
                $variations = $variation_data['variations'];
                if (count($variations) == 0) {
                    $variation_data = Helpers::get_edit_varient($product_variations, json_decode($detail->variation, true));
                    $variations = $variation_data['variations'];
                }
                $price = $detail->food->price + $variation_data['price'];
            } else {
                $price = $detail->food->price;
            }

            $price = $price - Helpers::product_discount_calculate(product: $detail->food, price: $price, restaurant: $order->restaurant);
            $price = $price * $detail->quantity + $detail->total_add_on_price;
            $cart['order_detail_id'] = $detail->id;
            $cart['cart_id'] = $key + 1;
            $cart['item_type'] = 'food';
            $cart['name'] = $detail->food->name;
            $cart['image_full_url'] = $detail->food?->image_full_url;
            $cart['maximum_cart_quantity'] =  min($detail->food?->maximum_cart_quantity ?? 9999999999, $detail->food?->stock_type == 'unlimited' ? 999999999 : $detail->food?->item_stock + $detail->quantity);
            $cart['item_id'] = $detail->food_id;
            $cart['quantity'] = $detail->quantity;
            $cart['price'] = $price;
            $cart['add_on_ids'] = $add_on_ids;
            $cart['add_on_qtys'] = $add_on_qtys;
            $cart['add_ons'] = $detail->add_ons;
            $cart['variation_options'] = $optionIds;
            $cart['variation_options_old'] = $old_selected_variations;
            $cart['addon_price'] = $detail->total_add_on_price;
            $cart['variations'] = json_decode($detail->variation, true);
            $carts[] = $cart;
        }

        Session::put('order_edit_cart', $carts);

        return response()->json([
            'view' => view('vendor-views.order.partials._food_list', compact('carts'))->render(),
        ]);
    }

    public function getSingleFoodPrice(Request $request)
    {

        $variation_price = 0;
        $old_selected_without_variation = 0;
        $old_selected_variations = [];

        if ($request->order_detail_id) {
            $order_detail = OrderDetail::where('id', $request->order_detail_id)->first();

            if ($request?->variation_options_old) {
                $old_selected_variations = $request->variation_options_old ?? [];
            }
            $old_selected_without_variation = $order_detail?->quantity ?? 0;
        }

        $product = Food::where('restaurant_id', Helpers::get_restaurant_id())->with('restaurant')->find($request->food_id);

        if ($product->maximum_cart_quantity && $request->quantity > $product->maximum_cart_quantity) {
            return response()->json([
                'data' => 'maximum_cart_quantity',
                'message' => translate('Maximum cart quantity for this item is ' . $product->maximum_cart_quantity),
            ], 203);
        }
        $addonAndVariationStock = Helpers::addonAndVariationStockCheck(
            product: $product,
            quantity: $request->quantity,
            variation_options: $request?->option_ids,
            old_selected_without_variation: $old_selected_without_variation,
            old_selected_variations: $old_selected_variations
        );
        if (data_get($addonAndVariationStock, 'out_of_stock') != null) {
            return response()->json([
                'data' => 'stock_out',
                'message' => data_get($addonAndVariationStock, 'out_of_stock'),
                'current_stock' => data_get($addonAndVariationStock, 'current_stock'),
                'id' => data_get($addonAndVariationStock, 'id'),
                'type' => data_get($addonAndVariationStock, 'type'),
            ], 203);
        }

        $price = $product->price;
        $carts = Session::get('order_edit_cart', []);
        $index = array_search($request->cart_id, array_column($carts, 'cart_id'));

        if ($index !== false) {
            $product_variations = json_decode($product->variations, true);
            if (count($carts[$index]['variations']) && count($product_variations)) {
                $variation_data = Helpers::get_edit_varient($product_variations, $carts[$index]['variations']);
                $variation_price = $variation_data['price'];
            }
            $price = $price + $variation_price;
            $price = $price - Helpers::product_discount_calculate(product: $product, price: $price, restaurant: $product->restaurant);
            $price =  $price * $request->quantity + $carts[$index]['addon_price'];

            if (data_get($carts[$index], 'order_detail_id') && $request->quantity != data_get($carts[$index], 'quantity')) {
                $edit_logs = Session::get('order_edit_logs', []);
                $edit_logs[] = 'edited_item_quantity';
                Session::put('order_edit_logs', $edit_logs);
            }
            $carts[$index]['quantity'] = $request->quantity;
            $carts[$index]['price'] =  $price;
        }

        Session::put('order_edit_cart', $carts);
        return response()->json([
            'data' => 0,
            'message' => translate('messages.quantity_updated_successfully'),
            'view' => view('vendor-views.order.partials._food_list', compact('carts'))->render(),
        ]);
    }


    public function update(Request $request, Order $order)
    {
        $carts = Session::get('order_edit_cart', []);
        $order_edit_logs = Session::get('order_edit_logs', []);

        $order = $this->makeEditOrderDetails(order: $order, carts: $carts, restaurant: $order->restaurant, editedBy: 'vendor', editLogs: $order_edit_logs);

        if (data_get($order, 'status_code') !== 200) {
            Toastr::error(data_get($order, 'message'));
            return back();
        }

        Toastr::success(translate('messages.order_updated_successfully'));
        return back();
    }

    public function quick_view(Request $request)
    {
        $product = Food::where('restaurant_id', Helpers::get_restaurant_id())->select(['id', 'name', 'image', 'veg', 'stock_type', 'item_stock', 'restaurant_id', 'discount', 'discount_type', 'description', 'maximum_cart_quantity', 'variations', 'add_ons', 'price'])->with('restaurant')->findOrFail($request->product_id);
        $item_type = 'food';
        $order_id = $request->order_id;

        return response()->json([
            'success' => 1,
            'view' => view('vendor-views.order.partials._quick-view', compact('product', 'order_id', 'item_type'))->render(),
        ]);
    }

    public function quick_view_cart_item(Request $request)
    {
        $carts = Session::get('order_edit_cart', []);
        $cart_item =  collect($carts)->firstWhere('cart_id', $request->cart_id);
        $order_id = $request->order_id;
        $item_id = $request->key;
        $product = Food::where('restaurant_id', Helpers::get_restaurant_id())->select(['id', 'name', 'image', 'veg', 'stock_type', 'item_stock', 'restaurant_id', 'discount', 'discount_type', 'description', 'maximum_cart_quantity', 'variations', 'add_ons', 'price'])->with('restaurant')->findOrFail($item_id);
        $item_type = 'food';

        $item_count = count($carts);

        return response()->json([
            'success' => 1,
            'view' => view('vendor-views.order.partials._quick-view-cart-item', compact('order_id', 'product', 'cart_item', 'item_id', 'item_type', 'item_count'))->render(),
        ]);
    }

    public function update_shipping(Request $request, Order $order)
    {

        $request->validate([
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
        ]);
        if ($request->latitude && $request->longitude) {
            $zone = Zone::where('id', $order->restaurant->zone_id)->whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))->first();
            if (!$zone) {
                Toastr::error(translate('messages.out_of_coverage'));
                return back();
            }
        }
        $address = [
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'floor' => $request->floor,
            'house' => $request->house,
            'road' => $request->road,
        ];

        $order->delivery_address = json_encode($address);
        $order->save();
        $this->makeEditOrderLogs($order->id, 'edited_delivery_info', 'vendor');

        Toastr::success(translate('messages.delivery_address_updated'));
        return back();
    }
}
