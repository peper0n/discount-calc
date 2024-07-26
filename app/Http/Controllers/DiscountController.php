<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'base_price' => 'required|numeric',
            'start_date' => 'required|date',
            'birth_date' => 'required|date',
            'payment_date' => 'nullable|date',
        ]);

        $basePrice = $validated['base_price'];
        $startDate = new \DateTime($validated['start_date']);
        $birthDate = new \DateTime($validated['birth_date']);
        $paymentDate = $validated['payment_date'] ? new \DateTime($validated['payment_date']) : new \DateTime();

        $childDiscount = $this->calculateChildDiscount($basePrice, $birthDate, $startDate);
        $earlyBookingDiscount = $this->calculateEarlyBookingDiscount($basePrice, $startDate, $paymentDate, $childDiscount);

        $totalDiscount = min($childDiscount + $earlyBookingDiscount, $basePrice);
        $finalPrice = $basePrice - $totalDiscount;

        return response()->json([
            'base_price' => $basePrice,
            'child_discount' => $childDiscount,
            'early_booking_discount' => $earlyBookingDiscount,
            'total_discount' => $totalDiscount,
            'final_price' => $finalPrice,
        ]);
    }

    private function calculateChildDiscount($basePrice, \DateTime $birthDate, \DateTime $startDate)
    {
        $age = $startDate->diff($birthDate)->y;

        if ($age >= 3 && $age < 6) {
            return round(0.8 * $basePrice, 2);
        } elseif ($age >= 6 && $age < 12) {
            return min(round(0.3 * $basePrice, 2), 4500);
        } elseif ($age >= 12 && $age < 18) {
            return round(0.1 * $basePrice, 2);
        } else {
            return 0;
        }
    }

    private function calculateEarlyBookingDiscount($basePrice, \DateTime $startDate, \DateTime $paymentDate, $childDiscount)
    {
        $discountedPrice = $basePrice - $childDiscount;
        $startMonthDay = (int)$startDate->format('md');
        $paymentMonthDay = (int)$paymentDate->format('md');
        $paymentYear = (int)$paymentDate->format('Y');

        if ($startMonthDay >= 401 && $startMonthDay <= 930) {
            if ($paymentMonthDay <= 1231 && $paymentYear < (int)$startDate->format('Y')) {
                return min(round(0.07 * $discountedPrice, 2), 1500);
            } elseif ($paymentMonthDay <= 131 && $paymentYear == (int)$startDate->format('Y')) {
                return min(round(0.05 * $discountedPrice, 2), 1500);
            } elseif ($paymentMonthDay <= 228 && $paymentYear == (int)$startDate->format('Y')) {
                return min(round(0.03 * $discountedPrice, 2), 1500);
            } else {
                return 0;
            }
        }
        else {
            if ($paymentMonthDay <= 630 && $paymentYear < (int)$startDate->format('Y')) {
                return min(round(0.07 * $discountedPrice, 2), 1500);
            } elseif ($paymentMonthDay <= 831 && $paymentYear < (int)$startDate->format('Y')) {
                return min(round(0.05 * $discountedPrice, 2), 1500);
            } elseif ($paymentMonthDay <= 930 && $paymentYear < (int)$startDate->format('Y')) {
                return min(round(0.03 * $discountedPrice, 2), 1500);
            } else {
                return 0;
            }
        }
    }
}
