@extends('master.layout')
@section('title')
@if(App::getlocale() == 'fa')
<title>هش بازار | بلاگ</title>
@else
<title>Hashbazaar | Blog</title>
@endif
@endsection
@section('content')
<?php

        foreach($posts as $key => $post){

                    $src = DB::connection('mysql2')->table('system_files')
                    ->where('field','featured_images')
                    ->where('attachment_id',$post->id)->first();
            if(is_null($src)){
                $img[$key] = null;
            }else{
                $firstDir = substr($src->disk_name,0,3);
                $secDir = substr($src->disk_name,3,3);
                $thirdDir = substr($src->disk_name,6,3);
                $img[$key] = env('BLOG_URL')
                        .$firstDir.'/'.$secDir.'/'.$thirdDir.'/'.$src->disk_name;
            }
        }
?>
<div class="container posts">
  <div class="row">
        @foreach($posts as $key => $post)
         <div class="col-md-4 col-sm-6">
          <article class="ContentSmallSize" style="background-color: white;">
              <a href="{{route('showPost',[session('locale'),$post->slug])}}">
              <figure>
               <img  height="225px" width="100%" src="{{$img[$key]}}" alt="{{ $post->title }}"/>
               <!-- <figcaption style="right: 0">{{ $post->title }}</figcaption> -->
              </figure>
              <div>
               <h5 style="color: black;">{{ $post->title }}</h5>
               <p style="color: black;">{{ $post->excerpt }}</p>
               <span style="color: black;"><time>{{ \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($post->published_at))->format('%B %d، %Y') }}</time></span>
              </div>
              </a>
          </article>
         </div> 
        @endforeach
   </div>
</div>
@include('master.footer')
<style type="text/css">
    .posts {margin-top: 120px;}
    .ContentSmallSize {
    flex: 0 1 calc(25% - 1em);
    text-align: right;
    margin-bottom: 1%;
    background-color: white;
    color: black;
}

.ContentSmallSize:hover {
    top: -2px;
    /*box-shadow: 0 4px 5px rgba(0,0,0,0.2);*/
    box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
}

@media screen and (max-width: 768px) {
    .ContentSmallSize {
        flex: 0 1 calc(50% - 1em);
    }
}
@media screen and (max-width: 600px) {
    .ContentSmallSize {
        flex: 0 1 calc(100% - 1.5em);
        margin-bottom: 4%;
    }
}
@media screen and (max-width: 414px) {
    .ContentSmallSize h3 {
        font-size: 1.3rem;
}
    .ContentSmallSize p {
        font-size: 1rem;
    }
}

.ContentSmallSize figure {
   position: relative;
}
.ContentSmallSize figcaption {
    position: absolute;
    top: 180px;
    color: white;
    background-color: black;
    padding: 4px 8px;
    font-size: 100%;
    font-weight: 400;
}
.ContentSmallSize h3 {
    color: black;
}
.ContentSmallSize span {
    color: #999999;
}
.ContentSmallSize p {
    color: black; direction: rtl;
}
.ContentSmallSize div {
    padding: 1% 2%;
}


</style>
<script>

    function rotate(obj) {
        obj.style.transform="rotateY(-180deg)";
        obj.firstElementChild.style.zIndex='1';
        obj.lastElementChild.style.zIndex='2';
        obj.style.transition ="all 2s"

    }
    function toBack(obj) {
        obj.style.transform="rotateY(0deg)";
        obj.firstElementChild.style.zIndex='2';
        obj.lastElementChild.style.zIndex='1';
        obj.style.transition ="all 2s"

    }



</script>
@endsection
