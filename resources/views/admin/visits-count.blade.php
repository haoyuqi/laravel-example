<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">Visits Count</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
            <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
        </div>
    </div>

    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-striped">

                @foreach($data as $item)
                <tr>
                    <td width="120px">{{ $item['name'] }}</td>
                    <td>{{ $item['value'] }}</td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
