<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdSubmitRequest;
use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\Province;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;

class AdSubmitController extends Controller
{
    public function create()
    {
        return view('front.submit-ad', [
            'categories' => Category::where('is_active', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
            'provinces' => Province::with('cities')
                ->orderBy('name')
                ->get(),
            'hasActiveServiceSubscription' => auth()->check()
                && auth()->user()->serviceSubscriptions()->current()->exists(),
            'hasActiveProductSubscription' => auth()->check()
                && auth()->user()->productSubscriptions()->current()->exists(),
        ]);
    }

    public function store(AdSubmitRequest $request, ImageService $images)
    {
        $data = $request->validated();

        if (
            $data['type'] === 'service' &&
            !auth()->user()->serviceSubscriptions()->current()->exists()
        ) {
            return redirect()
                ->route('service.plans')
                ->with('error', 'برای ثبت خدمت، ابتدا باید یک پلن اشتراک خدمات خریداری کنید.');
        }

        if (
            $data['type'] === 'product' &&
            !auth()->user()->productSubscriptions()->current()->exists()
        ) {
            return redirect()
                ->route('product.plans')
                ->with('error', 'برای ثبت آگهی محصول، ابتدا باید یک پلن اشتراک محصولات خریداری کنید.');
        }

        $this->createAd($data, $request->file('images', []), $images);

        return redirect()
            ->route('home')
            ->with('success', 'آگهی ثبت شد و پس از بررسی منتشر می‌شود.');
    }

    public function createAd(array $data, array $files, ImageService $images): Ad
    {
        return DB::transaction(function () use ($data, $files, $images) {
            $data['user_id'] = auth()->id();
            $data['status'] = 'pending';
            $data['expires_at'] = null;

            $ad = Ad::create($data);

            foreach ($files as $i => $file) {
                AdImage::create([
                    'ad_id' => $ad->id,
                    'path' => $images->upload($file),
                    'is_primary' => $i === 0,
                ]);
            }

            return $ad;
        });
    }
}
