import Router from "express"
import { getWithToken } from "../service/axiosService.js"
import { responseToDTO } from "../service/responseService.js"

const orderRouter = Router()


///orders?sortfield=t.rowid&sortorder=ASC&limit=100&page=0
orderRouter.get("/", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    const pageNbr = req.query.page
    const thirparty_ids = req.query.thirdparty_ids
    try{
        let url =`/orders?sortfield=t.rowid&sortorder=ASC&limit=100&page=${pageNbr}`
        if(thirparty_ids){
            url +=`&thirparty_ids=${thirparty_ids}`
        }
        const response = await getWithToken(url,token)
        res.send({
                status:"ok",
                result: responseToDTO(response,null)
            })
    }catch(error){
        console.log(error);
        res.send({
            status:"error"
        })
    }
})

orderRouter.get("/:id", async (req,res)=>{
    const token = req.get('DOLAPIKEY')
    const id = req.params.id
    try{
        const response = await getWithToken(`/orders/${id}`,token)
        res.send({
                status:"ok",
                result: responseToDTO(response,null)
            })
    }catch(error){
          console.log(error);
            res.send({
                status:"error"
            })
    }
})

export default orderRouter