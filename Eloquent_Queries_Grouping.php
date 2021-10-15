// Cutomers Index API
    public function customer(Request $request)
    {
        $customer = Lead::Query();

        // searching query
        $search = $request->input('search') ?? '';
        if ($search != '')
            // Grouping multiple orWheres() in a single where()
            $customer->where(function (EloquentBuilder $query) use ($search) {
                $query->whereHas('user', function (EloquentBuilder $q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })->orWhere('full_name', 'like', '%' . $search . '%')
                    ->orWhere('course', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%')
                    ->orWhere('phone_no', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });

        // filters
        if ($request->input('user_id'))
            $customer->where('user_id', $request->input('user_id'));

        if ($request->input('activity') == 'today')
            $customer->whereDate('created_at', Carbon::today());

        if ($request->input('status'))
            $customer->where('status', ucfirst($request->input('status')));

        if ($request->input('daterange') != '') {
            $dates = explode('-', $request->input('daterange'));
            $customer->whereBetween('date', [Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'), Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d')]);
        }

        // Sorting
        $sortBy = $request->input('sortBy') ?? 'created_at';
        $sortDirection = $request->input('sortDirection') ?? 'desc';
        $customer->orderBy($sortBy, $sortDirection);
        
        // Pagination
        $total_items = $customer->count() ?? 0;
        $perPage = $request->input('perPage') ?? 10;
        $total_pages = ceil($total_items / $perPage);
        $customer = $customer->latest()->paginate($perPage);
        $customer = LeadIndexResource::collection($customer);
        $data = [
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'customer' => $customer,
        ];

        return response()->json($data);
    }
