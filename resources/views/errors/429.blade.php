@extends('errors.layout')

@section('code', '429')
@section('title', __('Too many requests'))
@section('message', __('You are going a little too fast. Please wait a moment and try again.'))
