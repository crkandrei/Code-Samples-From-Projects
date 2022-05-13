<?php

namespace App\Http\Controllers;


use App\Order;
use Carbon\Carbon;
use App\Order_Element;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class DashboardController extends Controller
{
    //
    public function index(Request $request)
    {
        $user = Auth::user();
        //We need the year too because we can have data for same month but different year so we pick current year
        $month = (isset($request->month)) ? $request->month : Carbon::now()->month;
        $year = Carbon::now()->year;

        //In our help we calculate the year for previous month (Same if the previous month is not December but if the month is December that means the year for previous month is different)
        $previousMonth = ($month != 1) ? $month - 1 : 12;
        $previousYear = ($previousMonth != 12) ? $year : $year - 1;

        //Total Different Customers that made an order
        $totalCustomers = $this->getTotalCustomers($year, $month, $user->restaurant_id);
        $totalCustomersPreviousMonth = $this->getTotalCustomers($previousYear, $previousMonth, $user->restaurant_id);

        //Total Orders
        $totalOrders = $this->getTotalOrders($year, $month, $user->restaurant_id);
        $totalOrdersPreviousMonth = $this->getTotalOrders($previousYear, $previousMonth, $user->restaurant_id);

        //The sum of all Orders by month
        $totalReceipts = $this->getReceipts($year, $month, $user->restaurant_id);
        $totalReceiptsPreviousMonth = $this->getReceipts($previousYear, $previousMonth, $user->restaurant_id);

        //Average Receipt (sum of all orders/number of orders)
        $averageReceipts = ($totalOrders) ? $totalReceipts / $totalOrders : 0;
        $averageReceiptsPreviousMonth = ($totalOrdersPreviousMonth) ? $totalReceiptsPreviousMonth / $totalOrdersPreviousMonth : 0;

        //Top 5 Best selling Products
        $topProducts = $this->getTopProducts($year, $month, $user->restaurant_id);

        $sumPercent = ($totalReceiptsPreviousMonth) ? number_format((($totalReceipts - $totalReceiptsPreviousMonth) * 100 / $totalReceiptsPreviousMonth), 2) : 0;

        $customersPercent = ($totalCustomersPreviousMonth) ? number_format((($totalCustomers - $totalCustomersPreviousMonth) * 100 / $totalCustomersPreviousMonth), 2) : 0;

        $ordersPercent = ($totalOrdersPreviousMonth) ? number_format((($totalOrders - $totalOrdersPreviousMonth) * 100 / $totalOrdersPreviousMonth), 2) : 0;

        $averagePercent = ($averageReceiptsPreviousMonth) ? number_format((($averageReceipts - $averageReceiptsPreviousMonth) * 100 / $averageReceiptsPreviousMonth), 2) : 0;

        return view('home')
            ->with('month', $month)
            ->with('sumPercent', $sumPercent)
            ->with('customersPercent', $customersPercent)
            ->with('ordersPercent', $ordersPercent)
            ->with('averagePercent', $averagePercent)
            ->with('totalCustomers', $totalCustomers)
            ->with('totalOrders', $totalOrders)
            ->with('averageReceipts', $averageReceipts)
            ->with('topProducts', $topProducts)
            ->with('totalReceipts', $totalReceipts);
    }

    public function getTotalCustomers($year, $month, $idRestaurant)
    {
        $customers = Order::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('status_id', 4)
            ->where('restaurant_id', $idRestaurant)
            ->groupBy('user_id')
            ->get();

        return count($customers);
    }

    public function getTotalOrders($year, $month, $idRestaurant)
    {
        $orders = Order::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('status_id', 4)
            ->where('restaurant_id', $idRestaurant)
            ->count();

        return $orders;
    }

    public function getReceipts($year, $month, $idRestaurant)
    {
        $sum = Order::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('status_id', 4)
            ->where('restaurant_id', $idRestaurant)
            ->sum('total');

        return $sum;
    }

    public function getTopProducts($year, $month, $idRestaurant)
    {
        $topProducts = Order_Element::select('element_id', 'order_id', Order_Element::raw('SUM(quantity) AS issues'))
            ->whereHas('order', function ($q) use ($idRestaurant) {
                $q->where('restaurant_id', '=', $idRestaurant);
            })
            ->with('product')
            ->with('order')
            ->groupBy('element_id')
            ->orderBy('issues', 'DESC')
            ->limit(5)
            ->get();

        return $topProducts;
    }

}