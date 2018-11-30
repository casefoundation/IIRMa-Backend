@extends('layouts.backend')

@section('content')
    <div class="container">
        <div class="row">
            @include('admin.sidebar')

            <div class="col-md-9">
                <div class="panel panel-default">
                    <div class="panel-heading">Dataset Importer</div>
                    <div class="panel-body">

                        <form class="form-horizontal" method="post" action="{{ url('/admin/importer') }}" enctype="multipart/form-data">
                            {{ csrf_field() }}

                            <div class="form-group">
                                <label for="data_file" class="col-md-4 control-label">Data File:</label>
                                <div class="col-md-6">
                                    <input type="file" name="data_file" class="form-control" id="data_file" required="true">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="file_type" class="col-md-4 control-label">File type</label>
                                <div class="col-md-6">
                                    <select name="file_type" class="form-control" id="enity_type">
                                        <option value="csv">CSV</option>
                                        <option value="json">JSON</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="enity_type" class="col-md-4 control-label">Entity type</label>
                                <div class="col-md-6">
                                    <select name="enity_type" class="form-control" id="enity_type">
                                        <option value="company">Companies</option>
                                        <option value="finorg">Investors</option>
                                        <option value="deals">Deals</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-offset-4 col-md-4">
                                    <button type="submit" class="btn btn-primary" name="import">Import</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection