    // Leads Index API
    public function leads(Request $request)
    {
        $search = $request->input('search') ?? '';
        $perPage = $request->input('perPage') ?? 10;
        $sortBy = $request->input('sortBy') ?? 'created_at';
        $sortDirection = $request->input('sortDirection') ?? 'desc';

        $leads = Lead::Query();

        // searching query
        if ($search != '')
            $leads->where(function (EloquentBuilder $query) use ($search) {
                $query->whereHas('user', function (EloquentBuilder $q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })->orWhere('full_name', 'like', '%' . $search . '%')
                    ->orWhere('course', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%')
                    ->orWhere('phone_no', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });

        // filters
        if ($request->input('support_id'))
            $leads->where('user_id', $request->input('support_id'));

        if ($request->input('activity') == 'today')
            $leads->whereDate('created_at', Carbon::today());

        if ($request->input('status'))
            $leads->where('status', ucfirst($request->input('status')));

        if ($request->input('daterange') != '') {
            $dates = explode('-', $request->input('daterange'));
            $leads->whereBetween('date', [Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'), Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d')]);
        }

        $leads->orderBy($sortBy, $sortDirection);

        $total_items = $leads->count() ?? 0;
        $total_pages = ceil($total_items / $perPage);
        $leads = $leads->latest()->paginate($perPage);
        $leads = LeadIndexResource::collection($leads);
        $data = [
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'leads' => $leads,
        ];

        return response()->json($data);
    }
