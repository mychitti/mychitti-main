<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>GST</th>
            <th>Address</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['customers'] as $customer)
        <tr>
            <td>{{ $customer->f_name }}</td>
            <td>{{ $customer->phone }}</td>
            <td>{{ $customer->email }}</td>
            <td>{{ $customer->gst }}</td>
            <td>{{ $customer->address }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
